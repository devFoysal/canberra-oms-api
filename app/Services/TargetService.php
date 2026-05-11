<?php

namespace App\Services;

use App\Models\QuarterlyTarget;
use App\Models\MonthlyTarget;
use App\Models\WeeklyTarget;
use App\Models\DailyTarget;
use App\Models\Order;
use App\Models\OutletVisit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class TargetService
{
    // ─── Working days config ────────────────────────────────────────────────
    // Friday বাদ দেওয়া হয় (Bangladesh)
    const NON_WORKING_DAYS = [Carbon::FRIDAY];

    // ── Warning thresholds ─────────────────────────────────────────────────
    const DAILY_AMBER_THRESHOLD  = 70;   // < 70% by 3PM
    const DAILY_RED_THRESHOLD    = 50;   // < 50% by 5PM
    const WEEKLY_AMBER_THRESHOLD = 60;   // < 60% by Thursday
    const WEEKLY_RED_THRESHOLD   = 40;
    const MONTHLY_RED_THRESHOLD  = 50;   // < 50% at mid-month

    // ══════════════════════════════════════════════════════════════════════
    // TARGET CREATION & SPLIT
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Quarterly target তৈরি করে monthly/weekly/daily তে auto-split করে।
     *
     * @param array{
     *   salesRepId: int,
     *   targetType: string,
     *   quarterStartDate: string,
     *   quarterEndDate: string,
     *   quarterlyAmount: float,
     *   monthlyBreakdown?: array
     * } $data
     */
    public function createWithSplit(array $data): QuarterlyTarget
    {
        $quarterly = QuarterlyTarget::create([
            'sales_rep_id'       => $data['salesRepId'],
            'target_type'        => $data['targetType'],
            'quarter_start_date' => $data['quarterStartDate'],
            'quarter_end_date'   => $data['quarterEndDate'],
            'quarterly_amount'   => $data['quarterlyAmount'],
        ]);

        $this->generateMonthlySplit($quarterly, $data['monthlyBreakdown'] ?? []);

        return $quarterly->load('monthlyTargets.weeklyTargets.dailyTargets');
    }

    /**
     * Monthly targets তৈরি করে — custom override থাকলে সেটা use করে,
     * না থাকলে working days অনুযায়ী proportionally ভাগ করে।
     */
    private function generateMonthlySplit(QuarterlyTarget $quarterly, array $overrides): void
    {
        $start = Carbon::parse($quarterly->quarter_start_date);
        $end   = Carbon::parse($quarterly->quarter_end_date);

        // Quarter এর মধ্যে কোন কোন months আছে
        $months = $this->getMonthsInRange($start, $end);
        $totalWorkingDays = $this->countWorkingDays($start, $end);

        foreach ($months as $i => $monthStart) {
            $monthEnd = $monthStart->copy()->endOfMonth();
            if ($monthEnd->gt($end)) $monthEnd = $end->copy();

            // Custom override আছে কিনা check
            $overrideAmount = $overrides[$i]['target_amount'] ?? null;

            if ($overrideAmount !== null) {
                $monthAmount = (float) $overrideAmount;
            } else {
                // Working days অনুযায়ী proportional split
                $monthWorkingDays = $this->countWorkingDays($monthStart, $monthEnd);
                $monthAmount = $totalWorkingDays > 0
                    ? round(($monthWorkingDays / $totalWorkingDays) * $quarterly->quarterly_amount, 2)
                    : 0;
            }

            $monthly = MonthlyTarget::create([
                'quarterly_target_id' => $quarterly->id,
                'month'               => $monthStart->month,
                'year'                => $monthStart->year,
                'target_amount'       => $monthAmount,
                'achieved_amount'     => 0,
            ]);

            $this->generateWeeklySplit($monthly, $monthStart, $monthEnd);
        }
    }

    /**
     * Weekly targets তৈরি করে।
     */
    private function generateWeeklySplit(MonthlyTarget $monthly, Carbon $start, Carbon $end): void
    {
        $weeks = $this->getWeeksInRange($start, $end);
        $totalWorkingDays = $this->countWorkingDays($start, $end);

        foreach ($weeks as $week) {
            $weekWorkingDays = $this->countWorkingDays($week['start'], $week['end']);
            $weekAmount = $totalWorkingDays > 0
                ? round(($weekWorkingDays / $totalWorkingDays) * $monthly->target_amount, 2)
                : 0;

            $weekly = WeeklyTarget::create([
                'monthly_target_id' => $monthly->id,
                'week_number'       => $week['start']->weekOfYear,
                'start_date'        => $week['start']->toDateString(),
                'end_date'          => $week['end']->toDateString(),
                'target_amount'     => $weekAmount,
                'achieved_amount'   => 0,
            ]);

            $this->generateDailySplit($weekly, $week['start'], $week['end']);
        }
    }

    /**
     * Daily targets তৈরি করে (working days only)।
     */
    private function generateDailySplit(WeeklyTarget $weekly, Carbon $start, Carbon $end): void
    {
        $workingDays = $this->getWorkingDays($start, $end);
        $count = count($workingDays);
        if ($count === 0) return;

        $dailyAmount = round($weekly->target_amount / $count, 2);

        // Rounding error টি প্রথম দিনে যোগ করি
        $remainder = $weekly->target_amount - ($dailyAmount * $count);

        foreach ($workingDays as $i => $day) {
            DailyTarget::create([
                'weekly_target_id' => $weekly->id,
                'date'             => $day->toDateString(),
                'target_amount'    => $dailyAmount + ($i === 0 ? $remainder : 0),
                'achieved_amount'  => 0,
                'warning_level'    => 'none',
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ACHIEVEMENT CALCULATION
    // ══════════════════════════════════════════════════════════════════════

    /**
     * একজন SR এর নির্দিষ্ট period এর target vs achievement বের করে।
     *
     * @param int $salesRepId
     * @param string $period  'daily'|'weekly'|'monthly'|'quarterly'
     * @param string $targetType  'sales'|'outlet_visit'
     * @param string|null $date  (default: today)
     */
    public function getAchievement(
        int $salesRepId,
        string $period,
        string $targetType,
        ?string $date = null
    ): array {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        [$startDate, $endDate, $label] = match ($period) {
            'daily'     => [$date->copy()->startOfDay(), $date->copy()->endOfDay(), $date->format('d M Y')],
            'weekly'    => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek(), 'Week ' . $date->weekOfYear],
            'monthly'   => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $date->format('F Y')],
            'quarterly' => $this->getCurrentQuarterRange($date),
            default     => [$date->copy()->startOfDay(), $date->copy()->endOfDay(), 'Today'],
        };

        // Target amount
        $targetAmount = $this->getTargetAmountForPeriod(
            $salesRepId, $targetType, $startDate, $endDate
        );

        // Achieved amount
        $achievedAmount = $targetType === 'sales'
            ? $this->getSalesAchievement($salesRepId, $startDate, $endDate)
            : $this->getVisitAchievement($salesRepId, $startDate, $endDate);

        $percentage = $targetAmount > 0
            ? round(($achievedAmount / $targetAmount) * 100, 2)
            : 0;

        $shortfall = max($targetAmount - $achievedAmount, 0);
        $warningLevel = $this->computeWarningLevel($percentage, $period, $date);

        return [
            'target_amount'    => $targetAmount,
            'achieved_amount'  => $achievedAmount,
            'percentage'       => $percentage,
            'shortfall'        => $shortfall,
            'warning_level'    => $warningLevel,
            'period'           => $period,
            'label'            => $label,
        ];
    }

    /**
     * Orders table থেকে sales achievement বের করে।
     */
    private function getSalesAchievement(int $salesRepId, Carbon $start, Carbon $end): float
    {
        return (float) Order::where('sales_rep_id', $salesRepId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * OutletVisits table থেকে visit count বের করে।
     */
    private function getVisitAchievement(int $salesRepId, Carbon $start, Carbon $end): float
    {
        return (float) OutletVisit::where('sales_rep_id', $salesRepId)
            ->whereBetween('visited_at', [$start, $end])
            ->count();
    }

    /**
     * নির্দিষ্ট period এর জন্য target amount বের করে।
     */
    private function getTargetAmountForPeriod(
        int $salesRepId,
        string $targetType,
        Carbon $start,
        Carbon $end
    ): float {
        return (float) QuarterlyTarget::where('sales_rep_id', $salesRepId)
            ->where('target_type', $targetType)
            ->where('quarter_start_date', '<=', $start->toDateString())
            ->where('quarter_end_date', '>=', $end->toDateString())
            ->with(['monthlyTargets.weeklyTargets.dailyTargets' => function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            }])
            ->get()
            ->sum(fn($qt) =>
                $qt->monthlyTargets->sum(fn($m) =>
                    $m->weeklyTargets->sum(fn($w) =>
                        $w->dailyTargets->sum('target_amount')
                    )
                )
            );
    }

    // ══════════════════════════════════════════════════════════════════════
    // WARNING LEVEL
    // ══════════════════════════════════════════════════════════════════════

    public function computeWarningLevel(float $percentage, string $period, Carbon $now): string
    {
        return match (true) {
            $period === 'daily' && $now->hour >= 17 && $percentage < self::DAILY_RED_THRESHOLD   => 'red',
            $period === 'daily' && $now->hour >= 15 && $percentage < self::DAILY_AMBER_THRESHOLD => 'amber',
            $period === 'weekly' && $now->dayOfWeek >= Carbon::THURSDAY && $percentage < self::WEEKLY_RED_THRESHOLD   => 'red',
            $period === 'weekly' && $now->dayOfWeek >= Carbon::THURSDAY && $percentage < self::WEEKLY_AMBER_THRESHOLD => 'amber',
            $period === 'monthly' && $now->day >= 15 && $percentage < self::MONTHLY_RED_THRESHOLD => 'red',
            default => 'none',
        };
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function getWorkingDays(Carbon $start, Carbon $end): array
    {
        $days = [];
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $day) {
            if (!in_array($day->dayOfWeek, self::NON_WORKING_DAYS)) {
                $days[] = $day->copy();
            }
        }
        return $days;
    }

    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        return count($this->getWorkingDays($start, $end));
    }

    private function getMonthsInRange(Carbon $start, Carbon $end): array
    {
        $months = [];
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $months[] = $current->copy();
            $current->addMonth();
        }
        return $months;
    }

    private function getWeeksInRange(Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy()->startOfWeek();
        while ($current->lte($end)) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();
            $weekStart = $current->lt($start) ? $start->copy() : $current->copy();
            $weeks[] = ['start' => $weekStart, 'end' => $weekEnd];
            $current->addWeek();
        }
        return $weeks;
    }

    private function getCurrentQuarterRange(Carbon $date): array
    {
        $q = ceil($date->month / 3);
        $start = Carbon::create($date->year, ($q - 1) * 3 + 1, 1)->startOfDay();
        $end   = $start->copy()->addMonths(3)->subDay()->endOfDay();
        return [$start, $end, "Q{$q} {$date->year}"];
    }
}
