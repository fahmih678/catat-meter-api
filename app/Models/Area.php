<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'pam_id',
        'name',
        'code',
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
}
