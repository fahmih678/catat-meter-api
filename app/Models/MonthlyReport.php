<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pam_id',
        'month',
        'total_customers',
        'total_volume',
        'total_income',
        'generated_by',
    ];

    protected $casts = [
        'total_volume' => 'decimal:2',
        'total_income' => 'decimal:2',
    ];

    public function pam(): BelongsTo
    {
        return $this->belongsTo(Pam::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
