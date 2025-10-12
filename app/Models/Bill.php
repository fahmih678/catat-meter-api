<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'customer_id',
        'meter_reading_id',
        'bill_number',
        'reference_number',
        'volume_usage',
        'total_bill',
        'status',
        'due_date',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'volume_usage' => 'decimal:2',
        'total_bill' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function meterRecord(): BelongsTo
    {
        return $this->belongsTo(MeterRecord::class);
    }
}
