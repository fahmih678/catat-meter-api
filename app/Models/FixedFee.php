<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'pam_id',
        'tariff_group_id',
        'name',
        'amount',
        'effective_from',
        'effective_to',
        'description',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function tariffGroup(): BelongsTo
    {
        return $this->belongsTo(TariffGroup::class);
    }
}
