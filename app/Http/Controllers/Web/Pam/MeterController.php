<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\Meter;
use App\Models\Customer;
use App\Models\Pam;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MeterController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Generate unique meter number for PAM
     */
    private function generateUniqueMeterNumber($pamId): string
    {
        do {
            $pam = Pam::find($pamId);
            if (!$pam) {
                throw new \Exception('PAM not found');
            }

            // Format: MTR-PAMCODE-YYYYMMDD-XXX (3 digit random)
            $date = now()->format('Ymd');
            $random = mt_rand(100, 999);
            $meterNumber = 'MTR-' . strtoupper($pam->code) . '-' . $date . '-' . $random;

            // Check if unique
            $exists = Meter::where('pam_id', $pamId)
                ->where('meter_number', $meterNumber)
                ->exists();
        } while ($exists);

        return $meterNumber;
    }

    /**
     * Get all meters for a customer.
     */
    public function index($pamId, $customerId)
    {
        try {
            // Validate that customer belongs to PAM
            $customer = Customer::where('pam_id', $pamId)->findOrFail($customerId);

            $meters = Meter::where('customer_id', $customerId)
                ->orderBy('installed_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $meters
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to load customer meters: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data meter'
            ], 500);
        }
    }

    /**
     * Store a new meter for customer.
     */
    public function store(Request $request, $pamId, $customerId)
    {
        try {
            // Validate that customer belongs to PAM
            $customer = Customer::where('pam_id', $pamId)->findOrFail($customerId);

            // Validate the request data
            $validated = $request->validate([
                'meter_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('meters')->where(function ($query) use ($pamId) {
                        return $query->where('pam_id', $pamId);
                    })
                ],
                'installed_at' => 'required|date',
                'initial_installed_meter' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'is_active' => 'boolean',
            ], [
                'meter_number.unique' => 'Nomor meter sudah ada untuk PAM ini',
                'installed_at.required' => 'Tanggal pasang wajib diisi',
                'installed_at.date' => 'Format tanggal tidak valid',
                'initial_installed_meter.min' => 'Awal meter tidak boleh negatif',
            ]);

            // Create the meter
            $meter = DB::transaction(function () use ($validated, $customer, $pamId) {
                $meterData = [
                    'pam_id' => $pamId,
                    'customer_id' => $customer->id,
                    'meter_number' => $validated['meter_number'],
                    'installed_at' => $validated['installed_at'],
                    'initial_installed_meter' => $validated['initial_installed_meter'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'last_reading_at' => $validated['installed_at'],
                    'is_active' => $validated['is_active'] ?? true,
                ];

                return Meter::create($meterData);
            });

            return response()->json([
                'success' => true,
                'message' => 'Meter berhasil ditambahkan',
                'data' => $meter
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to create meter: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific meter details.
     */
    public function show($pamId, $meterId)
    {
        try {
            $meter = Meter::where('pam_id', $pamId)
                ->with(['customer:id,name,customer_number', 'pam:id,name,code'])
                ->findOrFail($meterId);

            return response()->json([
                'success' => true,
                'data' => $meter
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to load meter: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data meter'
            ], 500);
        }
    }

    /**
     * Update meter.
     */
    public function update(Request $request, $pamId, $meterId)
    {
        try {
            // Get meter
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            // Validate the request data
            $validated = $request->validate([
                'meter_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('meters')->where(function ($query) use ($pamId, $meterId) {
                        return $query->where('pam_id', $pamId)->where('id', '!=', $meterId);
                    })
                ],
                'installed_at' => 'required|date',
                'initial_installed_meter' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'is_active' => 'boolean',
            ], [
                'meter_number.unique' => 'Nomor meter sudah ada untuk PAM ini',
                'installed_at.required' => 'Tanggal pasang wajib diisi',
                'installed_at.date' => 'Format tanggal tidak valid',
                'initial_installed_meter.min' => 'Awal meter tidak boleh negatif',
            ]);

            // Update the meter
            $meter->update($validated);

            // Load relationships
            $meter->load(['customer:id,name,customer_number', 'pam:id,name,code']);

            return response()->json([
                'success' => true,
                'message' => 'Meter berhasil diperbarui',
                'data' => $meter
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to update meter: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete meter.
     */
    public function destroy($pamId, $meterId)
    {
        try {
            // Get meter
            $meter = Meter::where('pam_id', $pamId)->findOrFail($meterId);

            // Check if meter has readings
            if ($meter->meterReadings()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus meter yang memiliki catatan pembacaan'
                ], 422);
            }

            // Delete the meter
            $meter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Meter berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to delete meter: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus meter: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Generate meter number for customer.
     */
    public function generateMeterNumber($pamId, $customerId)
    {
        try {
            // Validate that customer belongs to PAM
            $customer = Customer::where('pam_id', $pamId)->findOrFail($customerId);

            $meterNumber = $this->generateUniqueMeterNumber($pamId);

            return response()->json([
                'success' => true,
                'meter_number' => $meterNumber,
                'message' => 'Nomor meter berhasil digenerate'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to generate meter number: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate nomor meter'
            ], 500);
        }
    }
}