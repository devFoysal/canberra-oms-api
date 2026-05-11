<?php
// ─── WeeklyTarget.php ────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyTarget extends Model
{
    protected $fillable = [
        'monthly_target_id',
        'week_number',
        'start_date',
        'end_date',
        'target_amount',
        'achieved_amount',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'target_amount'   => 'float',
        'achieved_amount' => 'float',
    ];

    public function monthlyTarget(): BelongsTo
    {
        return $this->belongsTo(MonthlyTarget::class);
    }

    public function dailyTargets(): HasMany
    {
        return $this->hasMany(DailyTarget::class);
    }
}
