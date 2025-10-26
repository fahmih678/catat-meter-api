<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'customer_id',
        'meter_number',
        'status',
        'installed_at',
        'initial_installed_meter',
        'notes',
        'last_recorded_at',
        'total_usage',
        'is_active',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_recorded_at' => 'datetime',
        'initial_installed_meter' => 'decimal:2',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }
}
