<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'pam_id',
        'area_id',
        'tariff_group_id',
        'customer_number',
        'name',
        'address',
        'phone',
        'status',
        'user_id',
        'is_active',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tariffGroup(): BelongsTo
    {
        return $this->belongsTo(TariffGroup::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function meter(): HasOne
    {
        return $this->hasOne(Meter::class)
            ->where('is_active', true)
            ->latest();
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
