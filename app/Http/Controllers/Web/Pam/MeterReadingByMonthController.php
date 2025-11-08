<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\Customer;
use App\Models\Pam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MeterReadingsExport;

class MeterReadingByMonthController extends Controller
{
    private $currentPamId;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->currentPamId = $request->route('pamId');
            return $next($request);
        });
    }

    /**
     * Display monthly meter readings overview
     */
    public function index()
    {
        try {
            // Get current PAM
            $pam = Pam::findOrFail($this->currentPamId);

            // Get available months with readings
            $monthsWithData = $this->getMonthsWithReadings();

            // Get current month and year
            $currentMonth = Carbon::now()->format('Y-m');
            $currentMonthDisplay = Carbon::now()->format('F Y');

            // Get statistics for current month
            $currentMonthStats = $this->getMonthlyStatistics($currentMonth);

            return view('dashboard.pam.meter-readings.index', compact(
                'pam',
                'monthsWithData',
                'currentMonth',
                'currentMonthDisplay',
                'currentMonthStats'
            ));
        } catch (\Throwable $th) {
            Log::error('Failed to load meter readings overview: ' . $th->getMessage());
            return back()->with('error', 'Gagal memuat data pembacaan meter');
        }
    }

    /**
     * Display meter readings for specific month
     */
    public function show($pamId, $month)
    {
        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Validate month format (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return back()->with('error', 'Format bulan tidak valid');
            }

            $monthCarbon = Carbon::createFromFormat('Y-m', $month . '-01');
            $monthDisplay = $monthCarbon->format('F Y');

            // Get meter readings for the month
            $meterReadings = MeterReading::with(['meter:id,meter_number', 'customerThrough:id,name,customer_number', 'readingBy:id,name'])
                ->where('pam_id', $pamId)
                ->whereMonth('reading_at', $monthCarbon->month)
                ->whereYear('reading_at', $monthCarbon->year)
                ->orderBy('reading_at')
                ->orderBy('meters.meter_number')
                ->get();

            // Group readings by meter
            $readingsByMeter = [];
            foreach ($meterReadings as $reading) {
                $meterId = $reading->meter_id;
                if (!isset($readingsByMeter[$meterId])) {
                    $readingsByMeter[$meterId] = [
                        'meter' => $reading->meter ?? (object)['meter_number' => 'Unknown'],
                        'customer' => $reading->customerThrough ?? null,
                        'readings' => [],
                        'total_usage' => 0,
                        'total_billed' => 0,
                        'total_paid' => 0
                    ];
                }
                $readingsByMeter[$meterId]['readings'][] = $reading;
                $readingsByMeter[$meterId]['total_usage'] += $reading->volume_usage ?? 0;
            }

            // Calculate totals
            $totalMeters = count($readingsByMeter);
            $totalReadings = $meterReadings->count();
            $totalUsage = $meterReadings->sum('volume_usage');
            $averageUsage = $totalReadings > 0 ? round($totalUsage / $totalReadings, 2) : 0;

            // Get statistics
            $statistics = $this->getMonthlyStatistics($month);

            // Get available months for navigation
            $monthsWithData = $this->getMonthsWithReadings();

            return view('dashboard.pam.meter-readings.show', compact(
                'pam',
                'month',
                'monthDisplay',
                'readingsByMeter',
                'totalMeters',
                'totalReadings',
                'totalUsage',
                'averageUsage',
                'statistics',
                'monthsWithData'
            ));
        } catch (\Throwable $th) {
            Log::error('Failed to load meter readings for month ' . $month . ': ' . $th->getMessage());
            return back()->with('error', 'Gagal memuat data pembacaan meter untuk bulan ' . $monthDisplay);
        }
    }

    /**
     * Export meter readings for specific month
     */
    public function export($pamId, $month)
    {
        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return back()->with('error', 'Format bulan tidak valid');
            }

            $monthCarbon = Carbon::createFromFormat('Y-m', $month . '-01');
            $monthDisplay = $monthCarbon->format('F Y');

            // Get meter readings for export
            $meterReadings = MeterReading::with(['meter:id,meter_number', 'customerThrough:id,name,customer_number', 'readingBy:id,name'])
                ->where('pam_id', $pamId)
                ->whereMonth('reading_at', $monthCarbon->month)
                ->whereYear('reading_at', $monthCarbon->year)
                ->orderBy('reading_at')
                ->orderBy('meters.meter_number')
                ->get();

            // Prepare export data
            $exportData = [];
            foreach ($meterReadings as $reading) {
                $exportData[] = [
                    'Tanggal Baca' => $reading->reading_at ? $reading->reading_at->format('d/m/Y') : '-',
                    'No. Pelanggan' => $reading->customerThrough ? $reading->customerThrough->customer_number : '-',
                    'Nama Pelanggan' => $reading->customerThrough ? $reading->customerThrough->name : '-',
                    'No. Meter' => $reading->meter ? $reading->meter->meter_number : '-',
                    'Angka Awal (m³)' => $reading->previous_reading ?? 0,
                    'Angka Akhir (m³)' => $reading->current_reading ?? 0,
                    'Pemakaian (m³)' => $reading->volume_usage ?? 0,
                    'Petugas Baca' => $reading->readingBy ? $reading->readingBy->name : '-',
                    'Status' => $reading->status === 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi',
                    'Catatan' => $reading->notes ?? '-'
                ];
            }

            // Generate filename
            $filename = 'pembacaan_meter_' . $pam->code . '_' . $month . '.xlsx';

            return Excel::download(new MeterReadingsExport($exportData), $filename);
        } catch (\Throwable $th) {
            Log::error('Failed to export meter readings: ' . $th->getMessage());
            return back()->with('error', 'Gagal mengekspor data pembacaan meter');
        }
    }

    /**
     * Get months that have meter readings data
     */
    private function getMonthsWithReadings()
    {
        try {
            $months = MeterReading::where('pam_id', $this->currentPamId)
                ->selectRaw('DATE_FORMAT(reading_at, "%Y-%m") as month')
                ->distinct()
                ->orderBy('month', 'desc')
                ->pluck('month')
                ->map(function ($month) {
                    $carbon = Carbon::createFromFormat('Y-m', $month);
                    return [
                        'value' => $month,
                        'display' => $carbon->format('F Y'),
                        'year' => $carbon->year,
                        'month' => $carbon->month
                    ];
                })
                ->toArray();

            return $months;
        } catch (\Throwable $th) {
            Log::error('Failed to get months with readings: ' . $th->getMessage());
            return [];
        }
    }

    /**
     * Get monthly statistics
     */
    private function getMonthlyStatistics($month)
    {
        try {
            $monthCarbon = Carbon::createFromFormat('Y-m', $month . '-01');

            $readings = MeterReading::where('pam_id', $this->currentPamId)
                ->whereMonth('reading_at', $monthCarbon->month)
                ->whereYear('reading_at', $monthCarbon->year)
                ->get();

            $totalReadings = $readings->count();
            $totalUsage = $readings->sum('volume_usage');
            $verifiedReadings = $readings->where('status', 'verified')->count();
            $pendingReadings = $readings->where('status', 'pending')->count();

            // Calculate percentage of verified readings
            $verificationRate = $totalReadings > 0 ? round(($verifiedReadings / $totalReadings) * 100, 1) : 0;

            return [
                'total_readings' => $totalReadings,
                'total_usage' => $totalUsage,
                'average_usage' => $totalReadings > 0 ? round($totalUsage / $totalReadings, 2) : 0,
                'verified_readings' => $verifiedReadings,
                'pending_readings' => $pendingReadings,
                'verification_rate' => $verificationRate,
                'last_reading_date' => $readings->max('reading_at') ?
                    $readings->max('reading_at')->format('d/m/Y') : '-'
            ];
        } catch (\Throwable $th) {
            Log::error('Failed to get monthly statistics: ' . $th->getMessage());
            return [
                'total_readings' => 0,
                'total_usage' => 0,
                'average_usage' => 0,
                'verified_readings' => 0,
                'pending_readings' => 0,
                'verification_rate' => 0,
                'last_reading_date' => '-'
            ];
        }
    }
}