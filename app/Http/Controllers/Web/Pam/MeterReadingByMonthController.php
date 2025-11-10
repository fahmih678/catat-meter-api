<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\RegisteredMonth;
use App\Models\Pam;
use App\Models\Area;
use App\Models\Bill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MeterReadingsExport;
use App\Services\MeterReadingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MeterReadingByMonthController extends Controller
{
    private MeterReadingService $meterReadingService;

    public function __construct(MeterReadingService $meterReadingService)
    {
        $this->meterReadingService = $meterReadingService;
    }

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
                ->withCount('meterReadings')
                ->withCount('paidMeterReadings')
                ->orderBy('period', 'desc')
                ->get();

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
    public function show($pamId, $month, Request $request)
    {
        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Validate month format
            if (!is_string($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $month)) {
                return back()->with('error', 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD, misalnya 2025-11-01.');
            }

            // Get registered month for statistics
            $registeredMonth = RegisteredMonth::with(['registeredBy:id,name'])
                ->where('pam_id', $pamId)
                ->where('period', $month)
                ->first();

            if (!$registeredMonth) {
                return back()->with('error', 'Periode '  . ' tidak ditemukan');
            }

            // Get areas for filter dropdown
            $areas = Area::where('pam_id', $pamId)->orderBy('name')->get();

            // Build query for meter readings
            $query = MeterReading::with([
                'meter:id,meter_number,customer_id',
                'meter.customer:id,name,customer_number,area_id',
                'meter.customer.area:id,name',
                'readingBy:id,name'
            ])
                ->where('pam_id', $pamId)
                ->where('registered_month_id', $registeredMonth->id);

            // Apply area filter if selected
            $selectedAreaId = $request->input('area_id');
            if ($selectedAreaId) {
                $query->whereHas('meter.customer', function ($q) use ($selectedAreaId) {
                    $q->where('area_id', $selectedAreaId);
                });
            }

            // Apply status filter if selected
            $selectedStatus = $request->input('status');
            if ($selectedStatus) {
                $query->where('status', $selectedStatus);
            }

            // Apply search filter if provided
            $search = $request->input('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('meter', function ($subQuery) use ($search) {
                        $subQuery->where('meter_number', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('meter.customer', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('customer_number', 'like', '%' . $search . '%');
                        });
                });
            }

            // Get meter readings with ordering and pagination
            $meterReadings = $query->orderBy('reading_at')->paginate(10);

            // Preserve query parameters in pagination
            $meterReadings->appends($request->query());

            // Get available months for navigation
            $monthsWithData = $this->getAvailableMonths($pamId);

            return view('dashboard.pam.meter-readings.show', compact(
                'pam',
                'month',
                'meterReadings',
                'areas',
                'selectedAreaId',
                'selectedStatus',
                'search'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found for show: ' . $th->getMessage());
            return back()->with('error', 'PAM tidak ditemukan');
        } catch (\Carbon\Exceptions\InvalidFormatException $th) {
            Log::error('Invalid month format for show: ' . $th->getMessage());
            return back()->with('error', 'Format bulan tidak valid');
        } catch (\Throwable $th) {
            Log::error('Failed to load meter readings: ' . $th->getMessage());
            return back()->with('error', 'Gagal memuat data pembacaan meter');
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
     * Update meter reading
     */
    public function update(Request $request, $pamId, $meterReadingId)
    {
        try {
            // Find meter reading
            $meterReading = MeterReading::with([
                'meter.customer',
                'registeredMonth'
            ])->where('id', $meterReadingId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate request data
            $validated = $request->validate([
                'current_reading' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Calculate volume usage using previous reading from database
            $previousReading = $meterReading->previous_reading;

            // Validate that current reading is not less than previous reading
            if ($validated['current_reading'] < $previousReading) {
                return response()->json([
                    'success' => false,
                    'message' => 'Angka akhir tidak boleh lebih kecil dari angka awal (' . number_format($previousReading, 1) . ' m³)'
                ], 422);
            }

            $volumeUsage = $validated['current_reading'] - $previousReading;

            DB::beginTransaction();

            try {
                // Update meter reading
                $updateData = [
                    'current_reading' => $validated['current_reading'],
                    'volume_usage' => $volumeUsage,
                    'notes' => $validated['notes'] ?? null,
                ];

                // Handle image upload
                if ($request->hasFile('photo')) {
                    // Delete old photo if exists
                    if ($meterReading->photo_url) {
                        $oldPath = str_replace('/storage/', '', $meterReading->photo_url);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $photoUrl = $this->handleImageUpload($request->file('photo'), $meterReading->meter->customer_id);
                    if ($photoUrl) {
                        $updateData['photo_url'] = $photoUrl;
                    }
                }

                $meterReading->update($updateData);

                Log::info("✅ MeterReading updated: {$meterReading->id}");

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pembacaan meter berhasil diperbarui',
                    'data' => $meterReading->fresh(['meter.customer', 'readingBy'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Meter reading not found for update: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data pembacaan meter tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $th) {
            Log::error('Validation failed for meter reading update: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $th->validator->errors()->all())
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to update meter reading: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pembacaan meter'
            ], 500);
        }
    }

    /**
     * Change status of meter reading
     */
    public function changeStatus(Request $request, $pamId, $meterReadingId)
    {
        try {
            // Find meter reading
            $meterReading = MeterReading::with([
                'meter.customer',
                'registeredMonth'
            ])->where('id', $meterReadingId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate request data
            $validated = $request->validate([
                'new_status' => 'required|in:draft,pending,paid',
                'current_status' => 'required|in:draft,pending,paid'
            ]);

            $originalStatus = $validated['current_status'];
            $newStatus = $validated['new_status'];

            // Validate status transition
            if ($originalStatus !== $meterReading->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status tidak valid. Silakan refresh halaman dan coba lagi.'
                ], 422);
            }

            // Check if status actually changed
            if ($originalStatus === $newStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status baru tidak boleh sama dengan status saat ini.'
                ], 422);
            }

            // Validate allowed transitions
            $allowedTransitions = [
                'draft' => ['pending', 'paid'],
                'pending' => ['paid', 'draft'],
                'paid' => ['pending', 'draft'],
            ];

            if (!in_array($newStatus, $allowedTransitions[$originalStatus])) {
                return response()->json([
                    'success' => false,
                    'message' => "Perubahan status dari {$originalStatus} ke {$newStatus} tidak diizinkan."
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update meter reading status
                $meterReading->update(['status' => $newStatus]);

                // Handle bill management based on status change
                switch (true) {
                    case $originalStatus === 'draft' && $newStatus === 'pending':
                        // Submit to pending
                        if (isset($this->meterReadingService)) {
                            $this->meterReadingService->submitMeterReadingToPending($meterReadingId);
                        }
                        Log::info("Meter reading submitted to pending: {$meterReadingId}");
                        break;

                    case $originalStatus === 'draft' && $newStatus === 'paid':
                        // Direct to paid - create bill
                        $this->createBillForMeterReading($meterReading);
                        Log::info("Meter reading set to paid directly: {$meterReadingId}");
                        break;

                    case $originalStatus === 'pending' && $newStatus === 'paid':
                        // Update existing bill to paid
                        $bill = Bill::where('meter_reading_id', $meterReading->id)->first();
                        if ($bill) {
                            $bill->update(['status' => 'paid']);
                        } else {
                            // Create bill if doesn't exist
                            $this->createBillForMeterReading($meterReading);
                        }
                        Log::info("Meter reading set to paid: {$meterReadingId}");
                        break;

                    case $originalStatus === 'pending' && $newStatus === 'draft':
                        // Back to draft - delete bill
                        $this->deleteBillForMeterReading($meterReading);
                        Log::info("Meter reading reverted to draft: {$meterReadingId}");
                        break;

                    case $originalStatus === 'paid' && $newStatus === 'pending':
                        // Back to pending - update bill status
                        $bill = Bill::where('meter_reading_id', $meterReading->id)->first();
                        if ($bill) {
                            $bill->update(['status' => 'pending']);
                        }
                        Log::info("Meter reading reverted to pending: {$meterReadingId}");
                        break;

                    case $originalStatus === 'paid' && $newStatus === 'draft':
                        // Back to draft - delete bill
                        $this->deleteBillForMeterReading($meterReading);
                        Log::info("Meter reading reverted to draft from paid: {$meterReadingId}");
                        break;
                }

                Log::info("✅ MeterReading status changed from {$originalStatus} to {$newStatus}: {$meterReadingId}");

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Status pembacaan meter berhasil diubah',
                    'data' => [
                        'old_status' => $originalStatus,
                        'new_status' => $newStatus
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Meter reading not found for status change: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data pembacaan meter tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $th) {
            Log::error('Validation failed for status change: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $th->validator->errors()->all())
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to change meter reading status: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status pembacaan meter'
            ], 500);
        }
    }

    /**
     * Create bill for meter reading
     */
    private function createBillForMeterReading($meterReading)
    {
        try {
            // Check if bill already exists
            $existingBill = Bill::where('meter_reading_id', $meterReading->id)->first();
            if ($existingBill) {
                return $existingBill;
            }

            // Generate bill number
            $billNumber = 'BILL-' . $meterReading->pam_id . '-' . date('Ymd') . '-' . $meterReading->id;

            // Calculate total bill (this would typically use tariff calculation)
            $totalBill = $this->calculateBillAmount($meterReading);

            // Create bill
            $bill = Bill::create([
                'pam_id' => $meterReading->pam_id,
                'customer_id' => $meterReading->meter->customer_id,
                'meter_reading_id' => $meterReading->id,
                'bill_number' => $billNumber,
                'reference_number' => $billNumber,
                'volume_usage' => $meterReading->volume_usage,
                'total_bill' => $totalBill,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(30),
                'issued_at' => Carbon::now(),
                'paid_by' => Auth::check() ? Auth::id() : null
            ]);

            Log::info('Bill created for meter reading', [
                'meter_reading_id' => $meterReading->id,
                'bill_id' => $bill->id,
                'amount' => $totalBill
            ]);

            return $bill;

        } catch (\Throwable $th) {
            Log::error('Failed to create bill for meter reading: ' . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Delete bill for meter reading
     */
    private function deleteBillForMeterReading($meterReading)
    {
        try {
            $bill = Bill::where('meter_reading_id', $meterReading->id)->first();
            if ($bill) {
                $billId = $bill->id;
                $bill->delete();

                Log::info('Bill deleted for meter reading', [
                    'meter_reading_id' => $meterReading->id,
                    'bill_id' => $billId
                ]);
            }
        } catch (\Throwable $th) {
            Log::error('Failed to delete bill for meter reading: ' . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Calculate bill amount (placeholder - would typically use tariff calculation)
     */
    private function calculateBillAmount($meterReading)
    {
        // This is a simplified calculation
        // In a real implementation, this would use the tariff system
        $baseRate = 5000; // Base rate per m³
        $administrativeFee = 10000; // Administrative fee

        return ($meterReading->volume_usage * $baseRate) + $administrativeFee;
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


    private function handleImageUpload($file, int $customerId): ?string
    {
        try {
            // Validate file type and size
            if (!$file->isValid()) {
                return null;
            }

            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return null;
            }

            // Maximum file size: 5MB
            if ($file->getSize() > 5 * 1024 * 1024) {
                return null;
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = 'meter_reading_' . $customerId . '_' . time() . '_' . Str::random(10) . '.' . $extension;

            // Create directory path based on date and customer
            $directory = 'meter_readings/' . date('Y/m');

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            if ($path) {
                // Return public URL
                return Storage::url($path);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading meter reading image: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'original_filename' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}
