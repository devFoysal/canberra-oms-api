<?php
// ─── DailyTarget.php ─────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyTarget extends Model
{
    protected $fillable = [
        'weekly_target_id',
        'date',
        'target_amount',
        'achieved_amount',
        'warning_level',   // 'none' | 'amber' | 'red'
    ];

    protected $casts = [
        'date'            => 'date',
        'target_amount'   => 'float',
        'achieved_amount' => 'float',
    ];

    public function weeklyTarget(): BelongsTo
    {
        return $this->belongsTo(WeeklyTarget::class);
    }
}
