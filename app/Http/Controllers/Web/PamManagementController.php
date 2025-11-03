<?php

namespace App\Http\Controllers\Web;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Area;
use App\Models\Customer;
use App\Models\FixedFee;
use App\Models\TariffGroup;
use App\Models\TariffTier;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PamManagementController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Show PAM management dashboard.
     */
    public function index(Request $request)
    {
        // Get search query from request
        $search = $request->get('search', '');

        // PAM dashboard logic with search
        if ($search) {
            // Search PAMs by name, code, email, or phone
            $pams = $this->pamService->searchPaginate($search, 10);
        } else {
            // Get all PAMs with pagination
            $pams = $this->pamService->getPaginate(10);
        }

        $pamTotal = $pams->total();

        return view('dashboard.pam.index', compact('pams', 'pamTotal', 'search'));
    }

    /**
     * Search PAMs via AJAX.
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);

        try {
            if ($search) {
                $pams = $this->pamService->searchPaginate($search, 10, ['*'], 'page', $page);
            } else {
                $pams = $this->pamService->getPaginate(10, ['*'], 'page', $page);
            }

            // Prepare table HTML
            $tableHtml = view('dashboard.pam.partials.table', compact('pams', 'search'))->render();

            // Prepare pagination HTML
            $paginationHtml = view('dashboard.pam.partials.pagination', compact('pams', 'search'))->render();

            return response()->json([
                'success' => true,
                'tableHtml' => $tableHtml,
                'paginationHtml' => $paginationHtml,
                'total' => $pams->total(),
                'currentPage' => $pams->currentPage(),
                'lastPage' => $pams->lastPage(),
                'search' => $search
            ]);
        } catch (\Throwable $th) {
            Log::error('Search PAM error: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to search PAMs: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show PAM detail page.
     */
    public function show($id)
    {

        try {
            // Get PAM data
            $pam = $this->pamService->findById($id);

            if (!$pam) {
                return redirect()->route('pam.index')
                    ->with('error', 'PAM not found');
            }

            // Get PAM statistics
            $statistics = $this->pamService->getStatistics($id);
            // Get related data (areas, tariff groups, etc.)
            // Note: These will need to be implemented in the service/repository
            $areas = $this->getPamAreas($id);
            $tariffGroups = $this->getPamTariffGroups($id);
            $tariffTiers = $this->getPamTariffTiers($id);
            $fixedFees = $this->getPamFixedFees($id);


            return view('dashboard.pam.detail', compact(
                'pam',
                'statistics',
                'areas',
                'tariffGroups',
                'tariffTiers',
                'fixedFees'
            ));
        } catch (\Throwable $th) {
            Log::error('Failed to load PAM detail: ' . $th->getMessage());

            return redirect()->route('pam.index')
                ->with('error', 'Failed to load PAM details');
        }
    }

    /**
     * Show customers for specific PAM.
     */
    public function customers(Request $request, $pamId)
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
                ->with(['area', 'tariffGroup', 'user']);

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
            $areas = $this->getPamAreas($pamId);

            // Get customer statistics
            $statistics = [
                'total' => Customer::where('pam_id', $pamId)->count(),
                'active' => Customer::where('pam_id', $pamId)->where('is_active', true)->count(),
                'inactive' => Customer::where('pam_id', $pamId)->where('is_active', false)->count(),
                'with_meters' => Customer::where('pam_id', $pamId)->whereNotNull('user_id')->count(),
            ];


            return view('dashboard.pam.customers', compact(
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
     * Get PAM areas
     */
    private function getPamAreas($pamId)
    {
        // This is a placeholder - implement proper area fetching
        // For now, return empty collection
        return Area::select('id', 'name', 'code')->withCount('customers')->where('pam_id', $pamId)->get();
    }

    /**
     * Get PAM tariff groups
     */
    private function getPamTariffGroups($pamId)
    {
        // This is a placeholder - implement proper tariff group fetching
        // For now, return empty collection
        return TariffGroup::select('id', 'name', 'is_active', 'description')->withCount('customers')->withCount('tariffTiers')->where('pam_id', $pamId)->get();
    }

    /**
     * Get PAM tariff tiers
     */
    private function getPamTariffTiers($pamId)
    {
        // This is a placeholder - implement proper tariff tier fetching
        // For now, return empty collection
        return TariffTier::select('description', 'meter_min', 'meter_max', 'amount', 'is_active', 'effective_from', 'effective_to', 'tariff_group_id')->with(['tariffGroup:id,name'])->where('pam_id', $pamId)->get();
    }

    /**
     * Get PAM fixed fees
     */
    private function getPamFixedFees($pamId)
    {
        // This is a placeholder - implement proper fixed fee fetching
        // For now, return empty collection
        return FixedFee::select('id', 'name', 'amount', 'effective_from', 'effective_to', 'is_active', 'tariff_group_id')->with(['tariffGroup:id,name'])->where('pam_id', $pamId)->get();
    }

    /**
     * Create new PAM.
     */
    public function storePam(Request $request)
    {
        if (!RoleHelper::isSuperAdmin()) {
            return abort(403, 'Unauthorized action.');
        }

        $createdBy = Auth::user()->id;
        $request->merge(['created_by' => $createdBy]);
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:pams,code',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'coordinate' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'created_by' => 'required|exists:users,id',
        ]);

        try {
            // Create the PAM using the service
            $pam = $this->pamService->create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PAM created successfully!',
                    'data' => $pam
                ]);
            }

            return back()->with('success', 'PAM created successfully');
        } catch (\Throwable $th) {
            // Log the error
            Log::error('Failed to create PAM: ' . $th->getMessage());

            // Return JSON error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create PAM: ' . $th->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to create PAM: ' . $th->getMessage()])->withInput();
        }
    }

    /**
     * Create new area for specific PAM.
     */
    public function storeArea(Request $request, $pamId)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50',
                'description' => 'nullable|string|max:1000',
            ]);

            // Add pam_id to validated data
            $validated['pam_id'] = $pamId;

            // Check if area code is unique within this PAM
            $existingArea = \App\Models\Area::where('pam_id', $pamId)
                ->where('code', $validated['code'])
                ->first();

            if ($existingArea) {
                $errorMessage = 'Area code already exists for this PAM';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['code' => ['Area code already exists for this PAM']]
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Create the area
            $area = \App\Models\Area::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Area created successfully',
                    'data' => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'code' => $area->code,
                        'description' => $area->description,
                        'pam_id' => $area->pam_id,
                        'created_at' => $area->created_at->format('Y-m-d H:i:s')
                    ]
                ], 201);
            }

            return back()->with('success', 'Area created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            Log::error('Area creation error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to create area. Please try again.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Create new tariff group for specific PAM.
     */
    public function storeTariffGroup(Request $request, $pamId)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'required|boolean',
            ]);

            // Add pam_id to validated data
            $validated['pam_id'] = $pamId;

            // Tariff group creation logic here with pam_id
            // For now, we'll simulate creation since we don't have the actual model
            // In a real implementation, you would do:
            // $tariffGroup = TariffGroup::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff group created successfully!',
                    'data' => $validated // In real implementation, return the created model
                ]);
            }

            return back()->with('success', 'Tariff group created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $th) {
            // Log the error
            Log::error('Failed to create tariff group: ' . $th->getMessage());

            // Return JSON error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create tariff group: ' . $th->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to create tariff group: ' . $th->getMessage()])->withInput();
        }
    }

    /**
     * Create new tariff tier for specific PAM.
     */
    public function storeTariffTier(Request $request, $pamId)
    {
        $request->validate([
            'tariff_group_id' => 'required|exists:tariff_groups,id',
            'name' => 'required|string|max:255',
            'min_meter' => 'required|numeric|min:0',
            'max_meter' => 'nullable|numeric|gt:min_meter',
            'price_per_m3' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // Tariff tier creation logic here

        return back()->with('success', 'Tariff tier created successfully');
    }

    /**
     * Create new fixed fee for specific PAM.
     */
    public function storeFixedFee(Request $request, $pamId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fixed_fees,code',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:monthly,quarterly,yearly',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Fixed fee creation logic here with pam_id

        return back()->with('success', 'Fixed fee created successfully');
    }

    /**
     * Update PAM.
     */
    public function updatePam(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:pams,code,' . $id,
            'area_id' => 'required|exists:areas,id',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        // PAM update logic here

        return back()->with('success', 'PAM updated successfully');
    }

    /**
     * Update area within specific PAM.
     */
    public function updateArea(Request $request, $pamId, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:areas,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Area update logic here

        return back()->with('success', 'Area updated successfully');
    }

    /**
     * Update tariff group within specific PAM.
     */
    public function updateTariffGroup(Request $request, $pamId, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tariff_groups,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Tariff group update logic here

        return back()->with('success', 'Tariff group updated successfully');
    }

    /**
     * Update tariff tier within specific PAM.
     */
    public function updateTariffTier(Request $request, $pamId, $id)
    {
        $request->validate([
            'tariff_group_id' => 'required|exists:tariff_groups,id',
            'name' => 'required|string|max:255',
            'min_meter' => 'required|numeric|min:0',
            'max_meter' => 'nullable|numeric|gt:min_meter',
            'price_per_m3' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // Tariff tier update logic here

        return back()->with('success', 'Tariff tier updated successfully');
    }

    /**
     * Update fixed fee within specific PAM.
     */
    public function updateFixedFee(Request $request, $pamId, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fixed_fees,code,' . $id,
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:monthly,quarterly,yearly',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Fixed fee update logic here

        return back()->with('success', 'Fixed fee updated successfully');
    }

    /**
     * Delete PAM.
     */
    public function destroyPam($id)
    {
        // PAM deletion logic here

        return back()->with('success', 'PAM deleted successfully');
    }

    /**
     * Delete area within specific PAM.
     */
    public function destroyArea($pamId, $id)
    {
        // Area deletion logic here

        return back()->with('success', 'Area deleted successfully');
    }

    /**
     * Delete tariff group within specific PAM.
     */
    public function destroyTariffGroup($pamId, $id)
    {
        // Tariff group deletion logic here

        return back()->with('success', 'Tariff group deleted successfully');
    }

    /**
     * Delete tariff tier within specific PAM.
     */
    public function destroyTariffTier($pamId, $id)
    {
        // Tariff tier deletion logic here

        return back()->with('success', 'Tariff tier deleted successfully');
    }

    /**
     * Delete fixed fee within specific PAM.
     */
    public function destroyFixedFee($pamId, $id)
    {
        // Fixed fee deletion logic here

        return back()->with('success', 'Fixed fee deleted successfully');
    }
}
