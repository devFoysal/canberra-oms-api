<?php
// ─── IdleEvent.php ───────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdleEvent extends Model
{
    protected $fillable = [
        'sales_rep_id',
        'start_time',
        'resolved_time',
        'duration_minutes',
        'reason_type',    // 'traveling'|'lunch_prayer'|'customer_meeting'|'market_closed'|'no_response'|'other'
        'reason_note',
        'is_resolved',
    ];

    protected $casts = [
        'start_time'    => 'datetime',
        'resolved_time' => 'datetime',
        'is_resolved'   => 'boolean',
    ];

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }
}
