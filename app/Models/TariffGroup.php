<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TariffGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'name',
        'is_active',
        'description',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function tariffTiers(): HasMany
    {
        return $this->hasMany(TariffTier::class);
    }

    public function fixedFees(): HasMany
    {
        return $this->hasMany(FixedFee::class);
    }
}
