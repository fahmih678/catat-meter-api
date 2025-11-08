<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\RegisteredMonth;
use App\Models\Pam;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MeterReadingsExport;

class MeterReadingByMonthController extends Controller
{
    /**
     * Display monthly meter readings overview
     */
    public function index($pamId)
    {
        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Get available months with readings from RegisteredMonth
            $monthsWithData = RegisteredMonth::with(['registeredBy:id,name'])
                ->where('pam_id', $pamId)
                ->orderBy('period', 'desc')
                ->get();

            // Add statistics to each month
            $monthsWithData = $monthsWithData->map(function ($month) use ($pamId) {
                $statistics = $this->getMonthStatistics($pamId, $month->period, $month);
                return (object) array_merge($month->toArray(), $statistics);
            });

            return view('dashboard.pam.meter-readings.index', compact(
                'pam',
                'monthsWithData',
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found for index: ' . $th->getMessage());
            return back()->with('error', 'PAM tidak ditemukan');
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
        // Initialize variables for error handling
        $monthDisplay = $month;
        $pam = null;

        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Validate month format (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return back()->with('error', 'Format bulan tidak valid');
            }

            $monthCarbon = Carbon::createFromFormat('Y-m', $month . '-01');
            $monthDisplay = $monthCarbon->format('F Y');

            // Get registered month data
            $registeredMonth = RegisteredMonth::with(['registeredBy:id,name'])
                ->where('pam_id', $pamId)
                ->where('period', $month)
                ->first();

            if (!$registeredMonth) {
                return back()->with('error', 'Data untuk bulan ' . $monthDisplay . ' belum terdaftar');
            }

            // Get meter readings for the month with proper relationships
            $meterReadings = MeterReading::with(['meter' => function ($query) {
                $query->select('id', 'meter_number', 'customer_id');
            }, 'meter.customer:id,name,customer_number', 'readingBy:id,name'])
                ->where('pam_id', $pamId)
                ->whereMonth('reading_at', $monthCarbon->month)
                ->whereYear('reading_at', $monthCarbon->year)
                ->orderBy('reading_at')
                ->orderBy('meter_id')
                ->get();

            // Group readings by meter
            $readingsByMeter = [];
            foreach ($meterReadings as $reading) {
                $meterId = $reading->meter_id;
                if (!isset($readingsByMeter[$meterId])) {
                    $readingsByMeter[$meterId] = [
                        'meter' => $reading->meter ?? (object)['meter_number' => 'Unknown'],
                        'customer' => $reading->meter && $reading->meter->customer ? $reading->meter->customer : null,
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

            // Get statistics from registered month and readings
            $statistics = $this->getMonthStatistics($pamId, $month, $registeredMonth, $meterReadings);

            // Get available months for navigation
            $monthsWithData = $this->getAvailableMonths($pamId);

            return view('dashboard.pam.meter-readings.show', compact(
                'pam',
                'month',
                'monthDisplay',
                'registeredMonth',
                'readingsByMeter',
                'totalMeters',
                'totalReadings',
                'totalUsage',
                'averageUsage',
                'statistics',
                'monthsWithData'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found: ' . $th->getMessage());
            return back()->with('error', 'PAM tidak ditemukan');
        } catch (\Carbon\Exceptions\InvalidFormatException $th) {
            Log::error('Invalid month format: ' . $th->getMessage());
            return back()->with('error', 'Format bulan tidak valid');
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
        // Initialize variables for error handling
        $monthDisplay = $month;
        $pam = null;

        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return back()->with('error', 'Format bulan tidak valid');
            }

            $monthCarbon = Carbon::createFromFormat('Y-m', $month . '-01');
            $monthDisplay = $monthCarbon->format('F Y');

            // Get meter readings for export with proper relationships
            $meterReadings = MeterReading::with(['meter' => function ($query) {
                $query->select('id', 'meter_number', 'customer_id');
            }, 'meter.customer:id,name,customer_number', 'readingBy:id,name'])
                ->where('pam_id', $pamId)
                ->whereMonth('reading_at', $monthCarbon->month)
                ->whereYear('reading_at', $monthCarbon->year)
                ->orderBy('reading_at')
                ->orderBy('meter_id')
                ->get();

            // Prepare export data
            $exportData = [];
            foreach ($meterReadings as $reading) {
                $customer = $reading->meter && $reading->meter->customer ? $reading->meter->customer : null;
                $exportData[] = [
                    'Tanggal Baca' => $reading->reading_at ? $reading->reading_at->format('d/m/Y') : '-',
                    'No. Pelanggan' => $customer ? $customer->customer_number : '-',
                    'Nama Pelanggan' => $customer ? $customer->name : '-',
                    'No. Meter' => $reading->meter ? $reading->meter->meter_number : '-',
                    'Angka Awal (m³)' => $reading->previous_reading ?? 0,
                    'Angka Akhir (m³)' => $reading->current_reading ?? 0,
                    'Pemakaian (m³)' => $reading->volume_usage ?? 0,
                    'Petugas Baca' => $reading->readingBy ? $reading->readingBy->name : '-',
                    'Status' => $reading->status === 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi',
                    'Catatan' => $reading->notes ?? '-'
                ];
            }

            // Check if there's data to export
            if (empty($exportData)) {
                return back()->with('error', 'Tidak ada data pembacaan meter untuk diekspor pada bulan ' . $monthDisplay);
            }

            // Generate filename
            $filename = 'pembacaan_meter_' . ($pam->code ?? 'pam') . '_' . $month . '.xlsx';

            return Excel::download(new MeterReadingsExport($exportData), $filename);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found for export: ' . $th->getMessage());
            return back()->with('error', 'PAM tidak ditemukan');
        } catch (\Carbon\Exceptions\InvalidFormatException $th) {
            Log::error('Invalid month format for export: ' . $th->getMessage());
            return back()->with('error', 'Format bulan tidak valid');
        } catch (\Maatwebsite\Excel\Exceptions\NoFilePathGivenException $th) {
            Log::error('Export file path error: ' . $th->getMessage());
            return back()->with('error', 'Gagal membuat file export');
        } catch (\Throwable $th) {
            Log::error('Failed to export meter readings: ' . $th->getMessage());
            return back()->with('error', 'Gagal mengekspor data pembacaan meter untuk bulan ' . $monthDisplay);
        }
    }

    /**
     * Get comprehensive month statistics combining RegisteredMonth and MeterReading data
     */
    private function getMonthStatistics($pamId, $month, $registeredMonth, $meterReadings = null)
    {
        try {
            // If meterReadings not provided, query them
            if ($meterReadings === null) {
                $meterReadings = MeterReading::where('pam_id', $pamId)
                    ->whereMonth('reading_at', Carbon::createFromFormat('Y-m', $month . '-01')->month)
                    ->whereYear('reading_at', Carbon::createFromFormat('Y-m', $month . '-01')->year)
                    ->get();
            }

            $totalReadings = $meterReadings->count();
            $totalUsage = $registeredMonth->total_usage ?? 0;
            $verifiedReadings = $meterReadings->where('status', 'verified')->count();
            $pendingReadings = $meterReadings->where('status', 'pending')->count();
            $verificationRate = $totalReadings > 0 ? round(($verifiedReadings / $totalReadings) * 100, 1) : 0;

            return [
                'total_readings' => $totalReadings,
                'total_usage' => $totalUsage,
                'average_usage' => $totalReadings > 0 ? round($totalUsage / $totalReadings, 2) : 0,
                'verified_readings' => $verifiedReadings,
                'pending_readings' => $pendingReadings,
                'verification_rate' => $verificationRate,
                'last_reading_date' => $meterReadings->max('reading_at') ?
                    $meterReadings->max('reading_at')->format('d/m/Y') : '-',
                'total_customers' => $registeredMonth->total_customers ?? 0,
                'total_bills' => $registeredMonth->total_bills ?? 0,
                'status' => $registeredMonth->status ?? 'closed',
                'status_display' => ($registeredMonth->status ?? 'closed') === 'open' ? 'Terbuka' : 'Ditutup',
                'registered_by' => $registeredMonth->registeredBy ? $registeredMonth->registeredBy->name : '-',
                'registered_at' => $registeredMonth->created_at ? $registeredMonth->created_at->format('d/m/Y H:i') : '-'
            ];
        } catch (\Throwable $th) {
            Log::error('Failed to get month statistics: ' . $th->getMessage());
            return $this->getDefaultStatistics();
        }
    }

    /**
     * Get available months for navigation
     */
    private function getAvailableMonths($pamId)
    {
        try {
            return RegisteredMonth::where('pam_id', $pamId)
                ->orderBy('period', 'desc')
                ->get()
                ->map(function ($month) {
                    $carbon = Carbon::createFromFormat('Y-m', $month->period . '-01');
                    return [
                        'value' => $month->period,
                        'display' => $carbon->format('F Y'),
                        'year' => $carbon->year,
                        'month' => $carbon->month,
                        'status' => $month->status,
                        'status_display' => $month->status === 'open' ? 'Terbuka' : 'Ditutup'
                    ];
                })
                ->toArray();
        } catch (\Throwable $th) {
            Log::error('Failed to get available months: ' . $th->getMessage());
            return [];
        }
    }

    /**
     * Get default statistics values
     */
    private function getDefaultStatistics()
    {
        return [
            'total_readings' => 0,
            'total_usage' => 0,
            'average_usage' => 0,
            'verified_readings' => 0,
            'pending_readings' => 0,
            'verification_rate' => 0,
            'last_reading_date' => '-',
            'total_customers' => 0,
            'total_bills' => 0,
            'status' => 'closed',
            'status_display' => 'Ditutup',
            'registered_by' => '-',
            'registered_at' => '-'
        ];
    }
}
