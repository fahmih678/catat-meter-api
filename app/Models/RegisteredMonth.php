<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
