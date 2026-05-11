<?php
// ─── OutletVisit.php ─────────────────────────────────────────────────────────

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletVisit extends Model
{
    protected $fillable = [
        'sales_rep_id',
        'outlet_name',
        'area',
        'contact_person',
        'contact_phone',
        'note',
        'latitude',
        'longitude',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }
}
