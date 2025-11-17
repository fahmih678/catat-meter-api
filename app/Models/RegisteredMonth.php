<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisteredMonth extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'period',
        'total_customers',
        'total_usage',
        'total_bills',
        'total_payment',
        'total_paid_customers',
        'status',
        'registered_by',
    ];

    protected $casts = [
        'total_usage' => 'decimal:2',
        'total_bills' => 'decimal:2',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function paidMeterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class, 'registered_month_id')
            ->where('status', 'paid');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function paidBills(): HasMany
    {
        return $this->hasMany(Bill::class, 'registered_month_id')
            ->where('status', 'paid');
    }
}
