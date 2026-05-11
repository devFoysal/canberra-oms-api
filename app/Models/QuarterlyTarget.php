<?php
// ─── QuarterlyTarget.php ─────────────────────────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuarterlyTarget extends Model
{
    protected $fillable = [
        'sales_rep_id',
        'target_type',        // 'sales' | 'outlet_visit'
        'quarter_start_date',
        'quarter_end_date',
        'quarterly_amount',
    ];

    protected $casts = [
        'quarter_start_date' => 'date',
        'quarter_end_date'   => 'date',
        'quarterly_amount'   => 'float',
    ];

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(MonthlyTarget::class);
    }

    public function getAchievedAmountAttribute(): float
    {
        return $this->monthlyTargets->sum('achieved_amount');
    }

    public function getAchievedPercentageAttribute(): float
    {
        if ($this->quarterly_amount <= 0) return 0;
        return round(($this->achieved_amount / $this->quarterly_amount) * 100, 2);
    }
}
