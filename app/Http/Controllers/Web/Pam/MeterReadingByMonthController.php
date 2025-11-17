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
                'readingBy:id,name',
                'latestBill'
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
            $meterReadings = $query->orderBy('meter_id', 'asc')->paginate(10);
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
     * Delete meter reading
     */
    public function deleteMeterReading(Request $request, $pamId, $meterReadingId)
    {
        try {
            // Find meter reading with relationships
            $meterReading = MeterReading::with([
                'meter.customer',
                'registeredMonth'
            ])->where('id', $meterReadingId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                // Store meter reading info for logging
                $readingInfo = [
                    'id' => $meterReading->id,
                    'customer_name' => $meterReading->meter->customer->name ?? '-',
                    'meter_number' => $meterReading->meter->meter_number ?? '-',
                    'reading_at' => $meterReading->reading_at
                ];

                // Delete associated bill if exists
                $bill = Bill::where('meter_reading_id', $meterReading->id)->first();
                if ($bill) {
                    $billId = $bill->id;
                    $bill->delete();
                    Log::info("Associated bill {$billId} deleted for meter reading {$meterReadingId}");
                }

                // Delete the meter reading
                $meterReading->delete();

                Log::info("Meter reading {$meterReadingId} deleted", $readingInfo);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pencatatan meter berhasil dihapus',
                    'data' => $readingInfo
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Meter reading not found for delete: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan'
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to delete meter reading: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pencatatan meter'
            ], 500);
        }
    }

    /**
     * Publish meter reading - change status from draft to pending and create bill using service
     */
    public function publishMeterReading(Request $request, $pamId, $meterReadingId)
    {
        try {
            // Find meter reading with basic validation
            $meterReading = MeterReading::where('id', $meterReadingId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate that meter reading is in draft status
            if ($meterReading->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pencatatan meter dengan status Draft yang dapat diterbitkan'
                ], 422);
            }

            // Use MeterReadingService to handle the submission
            $result = $this->meterReadingService->submitMeterReadingToPending($meterReadingId, [
                'user_id' => Auth::check() ? Auth::id() : null,
                'pam_id' => $pamId
            ]);

            if ($result && $result['success']) {
                return response()->json($result);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal menerbitkan pencatatan meter'
                ], 422);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Meter reading not found for publish: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan'
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to publish meter reading: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerbitkan pencatatan meter'
            ], 500);
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
     * Process bill payment - change status of pending bills to paid with meter reading updates
     */
    public function payBilling(Request $request, $pamId)
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'bill_ids' => 'required|array|min:1',
                'bill_ids.*' => 'integer|exists:bills,id',
                'payment_method' => 'nullable|string|in:cash,transfer,ewallet'
            ]);

            $billIds = $validatedData['bill_ids'];
            $paymentMethod = $validatedData['payment_method'] ?? 'cash';
            $updatedBills = [];
            $errors = [];

            // Verify bills belong to the specified PAM and are pending
            $bills = Bill::where('pam_id', $pamId)
                ->whereIn('id', $billIds)
                ->where('status', 'pending')
                ->with(['meterReading', 'customer'])
                ->get();

            if ($bills->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada tagihan pending yang ditemukan untuk ID yang dipilih'
                ], 404);
            }

            // Check if all requested bills exist and belong to the PAM
            $foundBillIds = $bills->pluck('id')->toArray();
            $missingBillIds = array_diff($billIds, $foundBillIds);

            if (!empty($missingBillIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa tagihan tidak ditemukan atau sudah dibayar: ' . implode(', ', $missingBillIds)
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Update each bill
                foreach ($bills as $bill) {
                    try {
                        $bill->update([
                            'status' => 'paid',
                            'paid_at' => now()->format('Y-m-d H:i:s'),
                            'paid_by' => Auth::check() ? Auth::id() : null,
                            'payment_method' => $paymentMethod
                        ]);

                        // Update meter reading status if exists
                        if ($bill->meterReading) {
                            $bill->meterReading->update([
                                'status' => 'paid',
                            ]);
                        }

                        $updatedBills[] = [
                            'id' => $bill->id,
                            'bill_number' => $bill->bill_number,
                            'customer_name' => $bill->customer->name ?? 'Tidak diketahui',
                            'total_bill' => $bill->total_bill,
                            'paid_at' => $bill->paid_at
                        ];

                        Log::info("Bill {$bill->id} marked as paid by user " . (Auth::check() ? Auth::id() : 'unknown'));
                    } catch (\Exception $e) {
                        $errors[] = [
                            'bill_id' => $bill->id,
                            'error' => $e->getMessage()
                        ];
                        Log::error("Failed to process payment for bill {$bill->id}: " . $e->getMessage());
                    }
                }

                DB::commit();

                if (!empty($errors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beberapa tagihan berhasil diproses dengan error',
                        'updated_bills' => $updatedBills,
                        'errors' => $errors
                    ], 207); // 207 Multi-Status
                }

                return response()->json([
                    'success' => true,
                    'message' => count($updatedBills) . ' tagihan berhasil dibayar',
                    'data' => [
                        'updated_bills' => $updatedBills,
                        'total_amount' => $bills->sum('total_bill'),
                        'pam_id' => $pamId
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found for payBilling: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'PAM tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $th) {
            Log::error('Validation failed for payBilling: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $th->validator->errors()->all())
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to process bill payment: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembayaran tagihan'
            ], 500);
        }
    }

    /**
     * Cancel billing - change meter reading status to draft and delete associated bill
     */
    public function cancelBilling(Request $request, $pamId, $meterReadingId)
    {
        try {
            // Find meter reading with bill relationship
            $meterReading = MeterReading::with(['latestBill', 'meter.customer'])
                ->where('id', $meterReadingId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate that meter reading is in pending status
            if ($meterReading->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pembacaan meter dengan status Menunggu yang dapat dibatalkan'
                ], 422);
            }

            // Validate that bill exists
            if (!$meterReading->latestBill) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada tagihan yang terkait dengan pembacaan meter ini'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Store information for logging and response
                $billingInfo = [
                    'meter_reading_id' => $meterReading->id,
                    'customer_name' => $meterReading->meter->customer->name ?? 'Tidak diketahui',
                    'bill_id' => $meterReading->latestBill->id,
                    'bill_number' => $meterReading->latestBill->bill_number,
                    'total_bill' => $meterReading->latestBill->total_bill
                ];

                // Delete the bill
                $billId = $meterReading->latestBill->id;
                $meterReading->latestBill->delete();

                // Update meter reading status to draft
                $meterReading->update(['status' => 'draft']);

                Log::info("Billing cancelled and bill {$billId} deleted for meter reading {$meterReadingId}", $billingInfo);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan berhasil dibatalkan dan status pembacaan meter diubah menjadi Draft',
                    'data' => $billingInfo
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Meter reading not found for cancel billing: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Pembacaan meter tidak ditemukan'
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to cancel billing: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan tagihan'
            ], 500);
        }
    }

    /**
     * Handle image upload for meter reading update
     */
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
