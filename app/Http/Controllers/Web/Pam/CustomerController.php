<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Area;
use App\Models\TariffGroup;
use App\Models\User;
use App\Models\Meter;
use App\Models\Pam;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Generate unique customer number for PAM
     */
    private function generateUniqueCustomerNumber($pamId): string
    {
        do {
            $pam = Pam::find($pamId);
            if (!$pam) {
                throw new \Exception('PAM not found');
            }

            // Format: PAMCODE-YYYYMMDD-XXXX (4 digit random)
            $date = now()->format('Ymd');
            $random = mt_rand(1000, 9999);
            $customerNumber = strtoupper($pam->code) . '-' . $date . '-' . $random;

            // Check if unique
            $exists = Customer::where('pam_id', $pamId)
                ->where('customer_number', $customerNumber)
                ->exists();
        } while ($exists);

        return $customerNumber;
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
     * Show customers for specific PAM.
     */
    public function index(Request $request, $pamId)
    {
        try {
            // Get PAM data
            $pam = $this->pamService->findById($pamId);

            if (!$pam) {
                return redirect()->route('pam.index')
                    ->with('error', 'PAM not found');
            }

            // Get search and filter parameters
            $search = $request->get('search', '');
            $areaId = $request->get('area_id', '');
            $status = $request->get('status', '');
            $perPage = $request->get('per_page', 10);

            // Build customer query for this PAM
            $query = Customer::where('pam_id', $pamId)
                ->with(['area', 'tariffGroup', 'user', 'meters' => function ($query) {
                    $query->where('is_active', true);
                }]);

            // Apply search filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('customer_number', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%');
                });
            }

            if ($areaId) {
                $query->where('area_id', $areaId);
            }

            if ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Get customers with pagination
            $customers = $query->orderBy('id')->paginate($perPage);

            // Get available areas for filter dropdown
            $areas = $this->pamService->getPamAreas($pamId);

            // Get customer statistics
            $statistics = [
                'total' => Customer::where('pam_id', $pamId)->count(),
                'active' => Customer::where('pam_id', $pamId)->where('is_active', true)->count(),
                'inactive' => Customer::where('pam_id', $pamId)->where('is_active', false)->count(),
                'with_meters' => Customer::where('pam_id', $pamId)->whereNotNull('user_id')->count(),
            ];
            // return response()->json($statistics);
            return view('dashboard.pam.customers.index', compact(
                'pam',
                'customers',
                'areas',
                'statistics',
                'search',
                'areaId',
                'status',
                'perPage'
            ));
        } catch (\Throwable $th) {
            Log::error('Failed to load PAM customers: ' . $th->getMessage());

            return redirect()->route('pam.show', $pamId)
                ->with('error', 'Failed to load customers');
        }
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request, $pamId)
    {
        try {
            // Get PAM data
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            // Validate the request data
            $validated = $request->validate([
                'customer_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('customers')->where(function ($query) use ($pamId) {
                        return $query->where('pam_id', $pamId);
                    })
                ],
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:20',
                'area_id' => 'required|exists:areas,id',
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'user_id' => 'nullable|exists:users,id',
                'is_active' => 'boolean',
                // Meter validation
                'meter_number' => 'nullable|string|max:50|unique:meters,meter_number',
                'installed_at' => 'nullable|date',
                'initial_installed_meter' => 'nullable|numeric|min:0',
                'meter_notes' => 'nullable|string',
            ], [
                'customer_number.unique' => 'Nomor pelanggan sudah ada untuk PAM ini',
                'area_id.exists' => 'Area tidak valid',
                'tariff_group_id.exists' => 'Grup tarif tidak valid',
                'user_id.exists' => 'Pengguna tidak valid',
                'meter_number.unique' => 'Nomor meter sudah ada',
            ]);

            // Add PAM ID to validated data
            $validated['pam_id'] = $pamId;

            // Create the customer using transaction
            $customer = DB::transaction(function () use ($validated, $request) {
                $customer = Customer::create($validated);

                // Create meter if meter_number is provided
                if ($request->filled('meter_number')) {
                    $meterData = [
                        'pam_id' => $validated['pam_id'],
                        'customer_id' => $customer->id,
                        'meter_number' => $request->meter_number,
                        'installed_at' => $request->installed_at,
                        'initial_installed_meter' => $request->initial_installed_meter ?? 0,
                        'notes' => $request->meter_notes,
                        'last_reading_at' => $request->installed_at,
                        'is_active' => true,
                    ];
                    Meter::create($meterData);
                }

                return $customer;
            });

            // Load relationships including meters
            $customer->load(['area', 'tariffGroup', 'user', 'meters']);

            return response()->json([
                'success' => true,
                'message' => 'Pelanggan berhasil ditambahkan',
                'data' => $customer
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to create customer: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pelanggan: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show customer details.
     */
    public function show($pamId, $id)
    {
        try {
            $customer = Customer::where('pam_id', $pamId)
                ->with([
                    'area:id,name',
                    'tariffGroup:id,name',
                    'tariffGroup.activeTariffTiers:id,tariff_group_id,description',
                    'tariffGroup.activeFixedFees:id,tariff_group_id,description',
                    'user',
                    'meters',
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to load customer: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pelanggan'
            ], 500);
        }
    }

    /**
     * Update customer.
     */
    public function update(Request $request, $pamId, $id)
    {
        try {
            // Get customer
            $customer = Customer::where('pam_id', $pamId)->findOrFail($id);

            // Validate the request data
            $validated = $request->validate([
                'customer_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('customers')->where(function ($query) use ($pamId, $id) {
                        return $query->where('pam_id', $pamId)->where('id', '!=', $id);
                    })
                ],
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:20',
                'area_id' => 'required|exists:areas,id',
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'user_id' => 'nullable|exists:users,id',
                'is_active' => 'boolean',
                // Meter validation
                'meter_number' => 'nullable|string|max:50|unique:meters,meter_number,' . ($request->meter_id ?? null) . ',id',
                'installed_at' => 'nullable|date',
                'initial_installed_meter' => 'nullable|numeric|min:0',
                'meter_notes' => 'nullable|string',
                'meter_id' => 'nullable|exists:meters,id',
                'meter_action' => 'nullable|in:add,update,remove',
            ], [
                'customer_number.unique' => 'Nomor pelanggan sudah ada untuk PAM ini',
                'area_id.exists' => 'Area tidak valid',
                'tariff_group_id.exists' => 'Grup tarif tidak valid',
                'user_id.exists' => 'Pengguna tidak valid',
                'meter_number.unique' => 'Nomor meter sudah ada',
            ]);

            // Update the customer and handle meter operations using transaction
            DB::transaction(function () use ($customer, $validated, $request) {
                $customer->update($validated);

                // Handle meter operations
                $meterAction = $request->meter_action;

                if ($meterAction === 'add' && $request->filled('meter_number')) {
                    // Add new meter
                    $meterData = [
                        'pam_id' => $customer->pam_id,
                        'customer_id' => $customer->id,
                        'meter_number' => $request->meter_number,
                        'installed_at' => $request->installed_at,
                        'initial_installed_meter' => $request->initial_installed_meter ?? 0,
                        'notes' => $request->meter_notes,
                        'last_reading_at' => $request->installed_at,
                        'is_active' => true,
                    ];
                    Meter::create($meterData);
                } elseif ($meterAction === 'update' && $request->filled('meter_id')) {
                    // Update existing meter
                    $meter = Meter::findOrFail($request->meter_id);
                    if ($meter->customer_id !== $customer->id) {
                        throw new \Exception('Meter tidak terkait dengan pelanggan ini');
                    }
                    $meter->update([
                        'meter_number' => $request->meter_number,
                        'installed_at' => $request->installed_at,
                        'initial_installed_meter' => $request->initial_installed_meter ?? $meter->initial_installed_meter,
                        'notes' => $request->meter_notes,
                    ]);
                } elseif ($meterAction === 'remove' && $request->filled('meter_id')) {
                    // Remove/deactivate meter
                    $meter = Meter::findOrFail($request->meter_id);
                    if ($meter->customer_id !== $customer->id) {
                        throw new \Exception('Meter tidak terkait dengan pelanggan ini');
                    }
                    $meter->update(['is_active' => false]);
                }
            });

            // Load relationships including meters
            $customer->load(['area', 'tariffGroup', 'user', 'meters']);

            return response()->json([
                'success' => true,
                'message' => 'Pelanggan berhasil diperbarui',
                'data' => $customer
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to update customer: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pelanggan: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete customer.
     */
    public function destroy($pamId, $id)
    {
        try {
            // Get customer
            $customer = Customer::where('pam_id', $pamId)->findOrFail($id);

            // Check if customer has meters or bills
            if ($customer->meters()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus pelanggan yang memiliki meter'
                ], 422);
            }

            if ($customer->bills()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus pelanggan yang memiliki tagihan'
                ], 422);
            }

            // Delete the customer
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pelanggan berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to delete customer: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pelanggan: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get areas and tariff groups for form dropdowns.
     */
    public function getFormData($pamId)
    {
        try {
            // Get PAM data
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            // Get available areas and tariff groups
            $areas = $this->pamService->getPamAreas($pamId, ['id', 'name', 'code']);
            $tariffGroups = $this->pamService->getPamTariffGroups($pamId, ['id', 'name']);

            // Get available users (for user_id assignment)
            $users = User::select('id', 'name')
                ->where('pam_id', $pamId)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'areas' => $areas,
                    'tariffGroups' => $tariffGroups,
                    'users' => $users
                ]
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to get form data: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data form'
            ], 500);
        }
    }

    /**
     * Generate customer number for PAM
     */
    public function generateCustomerNumber($pamId)
    {
        try {
            $customerNumber = $this->generateUniqueCustomerNumber($pamId);

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_number' => $customerNumber
                ],
                'message' => 'Nomor pelanggan berhasil digenerate'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to generate customer number: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate nomor pelanggan'
            ], 500);
        }
    }

    /**
     * Generate meter number for PAM
     */
    public function generateMeterNumber($pamId)
    {
        try {
            $meterNumber = $this->generateUniqueMeterNumber($pamId);

            return response()->json([
                'success' => true,
                'data' => [
                    'meter_number' => $meterNumber
                ],
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
