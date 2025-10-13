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
        'registered_month_id',
        'previous_reading',
        'current_reading',
        'volume_usage',
        'photo_url',
        'status',
        'notes',
        'reading_by',
        'reading_at',
    ];

    protected $casts = [
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
        'volume_usage' => 'decimal:2',
    ];

    // Direct relationships
    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function registeredMonth(): BelongsTo
    {
        return $this->belongsTo(RegisteredMonth::class);
    }

    public function readingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reading_by');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'meter_reading_id');
    }

    // Optimized relationships through joins
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'meter_id', 'id')
            ->join('meters', 'customers.id', '=', 'meters.customer_id')
            ->where('meters.id', $this->meter_id);
    }

    // HasOneThrough relationship for customer
    public function customerThrough()
    {
        return $this->hasOneThrough(
            Customer::class,
            Meter::class,
            'id',           // Foreign key on meters table
            'id',           // Foreign key on customers table  
            'meter_id',     // Local key on meter_readings table
            'customer_id'   // Local key on meters table
        );
    }

    // Get area through customer and meter
    public function area()
    {
        return $this->hasOneThrough(
            Area::class,
            Meter::class,
            'id',           // Foreign key on meters table
            'id',           // Foreign key on areas table
            'meter_id',     // Local key on meter_readings table
            'customer_id'   // Local key on meters table
        )->join('customers', 'areas.id', '=', 'customers.area_id')
            ->where('customers.id', function ($query) {
                $query->select('customer_id')
                    ->from('meters')
                    ->whereColumn('meters.id', 'meter_readings.meter_id');
            });
    }
}
