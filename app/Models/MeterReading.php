<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeterReading extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'meter_id',
        'period',
        'previous_reading',
        'current_reading',
        'volume_usage',
        'photo_url',
        'status',
        'reading_by',
    ];

    protected $casts = [
        'period' => 'date',
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
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

    public function readingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reading_by');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
