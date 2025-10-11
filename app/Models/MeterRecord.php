<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeterRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'meter_id',
        'period',
        'initial_meter',
        'final_meter',
        'volume_usage',
        'photo_url',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'period' => 'date',
        'initial_meter' => 'decimal:2',
        'final_meter' => 'decimal:2',
        'volume_usage' => 'decimal:2',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
