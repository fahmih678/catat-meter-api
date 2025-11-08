<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'code',
        'email',
        'logo_url',
        'description',
        'coordinate',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'coordinate' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function tariffGroups(): HasMany
    {
        return $this->hasMany(TariffGroup::class);
    }

    public function tariffTiers(): HasMany
    {
        return $this->hasMany(TariffTier::class);
    }

    public function fixedFees(): HasMany
    {
        return $this->hasMany(FixedFee::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function registeredMonths(): HasMany
    {
        return $this->hasMany(RegisteredMonth::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
