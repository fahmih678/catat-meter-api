<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\HasPamFiltering;
use App\Models\RegisteredMonth;
use App\Models\MeterReading;
use App\Models\Area;
use App\Models\Bill;
use App\Models\Customer;
use Carbon\Carbon;

class CatatMeterController extends Controller
{
    use HasPamFiltering;

    /**
     * Get meter reading summary statistics
     *
     * @param int $pamId
     * @param int|null $registeredMonthId
     * @return array
     */
    private function getMeterReadingSummary(int $pamId, ?int $registeredMonthId = null): array
    {
        $query = MeterReading::where('pam_id', $pamId);

        if ($registeredMonthId) {
            $query->where('registered_month_id', $registeredMonthId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending,
            COUNT(CASE WHEN status = ? THEN 1 END) as completed,
            COUNT(CASE WHEN status = ? THEN 1 END) as verified,
            COALESCE(SUM(volume_usage), 0) as total_volume
        ', ['pending', 'completed', 'verified'])->first();

        return [
            'total_readings' => (int) $summary->total,
            'status_counts' => [
                'pending' => [
                    'count' => (int) $summary->pending,
                    'percentage' => $summary->total > 0 ? round(($summary->pending / $summary->total) * 100, 1) : 0,
                ],
                'completed' => [
                    'count' => (int) $summary->completed,
                    'percentage' => $summary->total > 0 ? round(($summary->completed / $summary->total) * 100, 1) : 0,
                ],
                'verified' => [
                    'count' => (int) $summary->verified,
                    'percentage' => $summary->total > 0 ? round(($summary->verified / $summary->total) * 100, 1) : 0,
                ],
            ],
            'total_volume' => [
                'value' => (float) $summary->total_volume,
                'formatted' => number_format($summary->total_volume, 1, ',', '.') . ' m³'
            ],
        ];
    }
}
