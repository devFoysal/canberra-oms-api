<?php

namespace App\Models;

// File: app/Models/PaymentWarning.php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWarning extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'sales_rep_id',
        'warning_type',      // '15_days' | '30_days'
        'days_overdue',
        'order_total',
        'paid_amount',
        'due_amount',
        'note',
        'note_added_by',
        'note_added_at',
        'is_resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'order_total'    => 'float',
        'paid_amount'    => 'float',
        'due_amount'     => 'float',
        'is_resolved'    => 'boolean',
        'note_added_at'  => 'datetime',
        'resolved_at'    => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id', 'id');
    }

    public function noteAddedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'note_added_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeFifteenDay($query)
    {
        return $query->where('warning_type', '15_days');
    }

    public function scopeThirtyDay($query)
    {
        return $query->where('warning_type', '30_days');
    }
}
