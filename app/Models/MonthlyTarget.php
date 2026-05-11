<?php
// ─── MonthlyTarget.php ───────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyTarget extends Model
{
    protected $fillable = [
        'quarterly_target_id',
        'month',           // 1–12
        'year',
        'target_amount',
        'achieved_amount',
    ];

    protected $casts = [
        'target_amount'   => 'float',
        'achieved_amount' => 'float',
    ];

    public function quarterlyTarget(): BelongsTo
    {
        return $this->belongsTo(QuarterlyTarget::class);
    }

    public function weeklyTargets(): HasMany
    {
        return $this->hasMany(WeeklyTarget::class);
    }
}
