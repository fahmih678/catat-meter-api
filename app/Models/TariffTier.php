<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TariffTier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'tariff_group_id',
        'meter_min',
        'meter_max',
        'amount',
        'effective_from',
        'effective_to',
        'description',
        'is_active',
    ];

    protected $casts = [
        'meter_min' => 'decimal:2',
        'meter_max' => 'decimal:2',
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
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
