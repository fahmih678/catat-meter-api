<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\Meter;
use App\Http\Controllers\Api\V1\MeterReadingController as ApiMeterReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeterReadingController extends Controller
{
    protected $apiController;

    public function __construct(ApiMeterReadingController $apiController)
    {
        $this->apiController = $apiController;
    }

    /**
     * Get all meter readings for a specific meter.
     */
    public function index($pamId, $meterId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            $readings = MeterReading::where('meter_id', $meterId)
                ->with(['meter:id,meter_number', 'customer:id,name,customer_number'])
                ->orderBy('reading_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $readings
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to load meter readings: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pembacaan meter'
            ], 500);
        }
    }

    /**
     * Get specific meter reading details.
     */
    public function show($pamId, $meterId, $readingId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            $reading = MeterReading::where('meter_id', $meterId)
                ->where('id', $readingId)
                ->with(['meter:id,meter_number', 'customer:id,name,customer_number'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $reading
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to load meter reading: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pembacaan meter'
            ], 500);
        }
    }

    /**
     * Store a new meter reading.
     */
    public function store(Request $request, $pamId, $meterId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            // Validate the request data
            $validated = $request->validate([
                'reading_date' => 'required|date',
                'current_reading' => 'required|numeric|min:0',
                'previous_reading' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:pending,verified',
                'reader_name' => 'nullable|string|max:255',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ], [
                'reading_date.required' => 'Tanggal pembacaan wajib diisi',
                'reading_date.date' => 'Format tanggal tidak valid',
                'current_reading.required' => 'Angka meter akhir wajib diisi',
                'current_reading.numeric' => 'Angka meter harus berupa angka',
                'current_reading.min' => 'Angka meter tidak boleh negatif',
                'previous_reading.min' => 'Angka meter sebelumnya tidak boleh negatif',
                'status.in' => 'Status harus pending atau verified',
                'photo.image' => 'File harus berupa gambar',
                'photo.mimes' => 'Format gambar harus jpeg, png, atau jpg',
                'photo.max' => 'Ukuran gambar maksimal 2MB',
            ]);

            // Calculate usage
            $usage = $validated['current_reading'] - ($validated['previous_reading'] ?? 0);
            if ($usage < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Angka meter akhir tidak boleh lebih kecil dari angka meter sebelumnya'
                ], 422);
            }

            // Create the meter reading
            $reading = MeterReading::create([
                'meter_id' => $meterId,
                'customer_id' => $meter->customer_id,
                'reading_date' => $validated['reading_date'],
                'previous_reading' => $validated['previous_reading'] ?? 0,
                'current_reading' => $validated['current_reading'],
                'usage' => $usage,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'reader_name' => $validated['reader_name'] ?? Auth::user()->name,
                'pam_id' => $pamId,
            ]);

            // Handle photo upload if exists
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('meter-reading-photos', 'public');
                $reading->update(['photo' => $photoPath]);
            }

            // Load relationships
            $reading->load(['meter:id,meter_number', 'customer:id,name,customer_number']);

            return response()->json([
                'success' => true,
                'message' => 'Pembacaan meter berhasil ditambahkan',
                'data' => $reading
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to create meter reading: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pembacaan meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update meter reading.
     */
    public function update(Request $request, $pamId, $meterId, $readingId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            // Get meter reading
            $reading = MeterReading::where('meter_id', $meterId)
                ->where('id', $readingId)
                ->firstOrFail();

            // Validate the request data
            $validated = $request->validate([
                'reading_date' => 'required|date',
                'current_reading' => 'required|numeric|min:0',
                'previous_reading' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:pending,verified',
                'reader_name' => 'nullable|string|max:255',
            ], [
                'reading_date.required' => 'Tanggal pembacaan wajib diisi',
                'reading_date.date' => 'Format tanggal tidak valid',
                'current_reading.required' => 'Angka meter akhir wajib diisi',
                'current_reading.numeric' => 'Angka meter harus berupa angka',
                'current_reading.min' => 'Angka meter tidak boleh negatif',
                'previous_reading.min' => 'Angka meter sebelumnya tidak boleh negatif',
                'status.in' => 'Status harus pending atau verified',
            ]);

            // Calculate usage
            $usage = $validated['current_reading'] - ($validated['previous_reading'] ?? 0);
            if ($usage < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Angka meter akhir tidak boleh lebih kecil dari angka meter sebelumnya'
                ], 422);
            }

            // Update the meter reading
            $reading->update([
                'reading_date' => $validated['reading_date'],
                'previous_reading' => $validated['previous_reading'] ?? 0,
                'current_reading' => $validated['current_reading'],
                'usage' => $usage,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'reader_name' => $validated['reader_name'] ?? $reading->reader_name,
            ]);

            // Load relationships
            $reading->load(['meter:id,meter_number', 'customer:id,name,customer_number']);

            return response()->json([
                'success' => true,
                'message' => 'Pembacaan meter berhasil diperbarui',
                'data' => $reading
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to update meter reading: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pembacaan meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete meter reading.
     */
    public function destroy($pamId, $meterId, $readingId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            // Get meter reading
            $reading = MeterReading::where('meter_id', $meterId)
                ->where('id', $readingId)
                ->firstOrFail();

            // Check if reading has associated bills
            if ($reading->bills()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus pembacaan yang memiliki tagihan'
                ], 422);
            }

            // Delete the reading
            $reading->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pembacaan meter berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to delete meter reading: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pembacaan meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get meter reading statistics
     */
    public function statistics($pamId, $meterId)
    {
        try {
            // Validate that meter belongs to PAM
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            $readings = MeterReading::where('meter_id', $meterId)
                ->orderBy('reading_date', 'desc')
                ->get();

            $totalReadings = $readings->count();
            $verifiedReadings = $readings->where('status', 'verified')->count();
            $pendingReadings = $readings->where('status', 'pending')->count();
            $totalUsage = $readings->sum('usage');
            $averageUsage = $totalReadings > 0 ? $totalUsage / $totalReadings : 0;

            $lastReading = $readings->first();
            $lastReadingDate = $lastReading ? $lastReading->reading_date : null;
            $lastReadingValue = $lastReading ? $lastReading->current_reading : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_readings' => $totalReadings,
                    'verified_readings' => $verifiedReadings,
                    'pending_readings' => $pendingReadings,
                    'total_usage' => $totalUsage,
                    'average_usage' => round($averageUsage, 2),
                    'last_reading_date' => $lastReadingDate,
                    'last_reading_value' => $lastReadingValue,
                ]
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to get meter reading statistics: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik pembacaan meter'
            ], 500);
        }
    }
}
