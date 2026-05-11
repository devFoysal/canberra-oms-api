<?php

namespace App\Services;

use App\Models\IdleEvent;
use App\Models\Order;
use App\Models\User;
use App\Jobs\SendIdleNotificationJob;
use Carbon\Carbon;

class IdleDetectionService
{
    const IDLE_THRESHOLD_MINUTES = 90;
    const ACTIVE_HOURS_START     = 9;   // 9 AM
    const ACTIVE_HOURS_END       = 18;  // 6 PM

    // ══════════════════════════════════════════════════════════════════════
    // CRON — প্রতি 5 মিনিটে এই method call হবে
    // ══════════════════════════════════════════════════════════════════════

    /**
     * সব active SR দের check করে।
     * Scheduler এ call করো: $schedule->call(fn() => app(IdleDetectionService::class)->checkAllSalesReps())->everyFiveMinutes()
     */
    public function checkAllSalesReps(): void
    {
        $now = Carbon::now();

        // Active hours এর বাইরে check করবো না
        if ($now->hour < self::ACTIVE_HOURS_START || $now->hour >= self::ACTIVE_HOURS_END) {
            return;
        }

        // সব active SR দের নিয়ে আসো
        $salesReps = User::where('role', 'sales_rep')
            ->where('is_active', true)
            ->get();

        foreach ($salesReps as $sr) {
            $this->checkSingleSalesRep($sr);
        }
    }

    /**
     * একজন SR কে check করে।
     */
    public function checkSingleSalesRep(User $sr): void
    {
        // ইতিমধ্যে unresolved idle event আছে কিনা
        $existingIdle = IdleEvent::where('sales_rep_id', $sr->id)
            ->where('is_resolved', false)
            ->latest('start_time')
            ->first();

        if ($existingIdle) {
            // Duration আপডেট করো
            $existingIdle->update([
                'duration_minutes' => Carbon::now()->diffInMinutes($existingIdle->start_time),
            ]);
            return; // Already notified
        }

        // শেষ order কখন ছিল?
        $lastOrderTime = Order::where('sales_rep_id', $sr->id)
            ->latest('created_at')
            ->value('created_at');

        // আজকে কোনো order না থাকলে login time থেকে count করো
        if (!$lastOrderTime) {
            $lastOrderTime = Carbon::today()->setHour(self::ACTIVE_HOURS_START);
        }

        $minutesSinceLastOrder = Carbon::now()->diffInMinutes($lastOrderTime);

        if ($minutesSinceLastOrder >= self::IDLE_THRESHOLD_MINUTES) {
            // Idle event তৈরি করো
            $idleEvent = IdleEvent::create([
                'sales_rep_id'     => $sr->id,
                'start_time'       => $lastOrderTime->addMinutes(self::IDLE_THRESHOLD_MINUTES),
                'duration_minutes' => $minutesSinceLastOrder,
                'is_resolved'      => false,
            ]);

            // Push notification পাঠাও
            dispatch(new SendIdleNotificationJob($sr, $idleEvent));
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // SR এর current idle status
    // ══════════════════════════════════════════════════════════════════════

    public function getMyStatus(int $salesRepId): array
    {
        $unresolvedIdle = IdleEvent::where('sales_rep_id', $salesRepId)
            ->where('is_resolved', false)
            ->latest('start_time')
            ->first();

        if (!$unresolvedIdle) {
            return ['is_idle' => false];
        }

        return [
            'is_idle'          => true,
            'idle_since'       => $unresolvedIdle->start_time->toIso8601String(),
            'duration_minutes' => Carbon::now()->diffInMinutes($unresolvedIdle->start_time),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // RESOLVE — SR reason submit করলে
    // ══════════════════════════════════════════════════════════════════════

    public function logAndResolve(int $salesRepId, array $data): IdleEvent
    {
        // সবচেয়ে recent unresolved event খোঁজো
        $idleEvent = IdleEvent::where('sales_rep_id', $salesRepId)
            ->where('is_resolved', false)
            ->latest('start_time')
            ->firstOrFail();

        $idleEvent->update([
            'resolved_time'    => Carbon::now(),
            'duration_minutes' => Carbon::now()->diffInMinutes($idleEvent->start_time),
            'reason_type'      => $data['reason_type'],
            'reason_note'      => $data['reason_note'] ?? null,
            'is_resolved'      => true,
        ]);

        return $idleEvent;
    }

    // ══════════════════════════════════════════════════════════════════════
    // ADMIN — idle events list
    // ══════════════════════════════════════════════════════════════════════

    public function getEventsForAdmin(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = IdleEvent::with('salesRep:id,name')
            ->latest('start_time');

        if (!empty($filters['sales_rep_id'])) {
            $query->where('sales_rep_id', $filters['sales_rep_id']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }

        if (isset($filters['is_resolved'])) {
            $query->where('is_resolved', filter_var($filters['is_resolved'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->get()->map(function ($event) {
            $event->sales_rep_name = $event->salesRep?->name;
            return $event;
        });
    }
}
