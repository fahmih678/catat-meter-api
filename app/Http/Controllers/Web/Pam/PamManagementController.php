<?php

namespace App\Http\Controllers\Web\Pam;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Customer;
use App\Models\FixedFee;
use App\Models\TariffGroup;
use App\Models\TariffTier;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            $areas = $this->pamService->getPamAreas($id);
            $tariffGroups = $this->pamService->getPamTariffGroups($id);
            $tariffTiers = $this->pamService->getPamTariffTiers($id);
            $fixedFees = $this->pamService->getPamFixedFees($id);

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
     * Create new PAM.
     */
    public function store(Request $request)
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
     * Edit PAM - Return PAM data for AJAX request.
     */
    public function edit($id)
    {
        try {
            $pam = $this->pamService->findById($id);

            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pam
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to get PAM data for edit: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load PAM data: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update PAM.
     */
    public function update(Request $request, $id)
    {
        if (!RoleHelper::isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error_code' => 'INSUFFICIENT_ROLE'
            ], 403);
        }

        try {
            // Check if PAM exists
            $pam = $this->pamService->findById($id);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:pams,code,' . $id,
                'address' => 'required|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255|unique:pams,email,' . $id,
                'coordinate' => 'nullable|string|max:255',
                'logo_url' => 'nullable|url|max:500',
                'is_active' => 'required|boolean',
            ], [
                'code.unique' => 'The code has already been taken.',
                'email.unique' => 'The email has already been taken.',
                'logo_url.url' => 'The logo URL must be a valid URL.',
            ]);

            // Handle coordinate - convert to JSON if needed
            if (!empty($validated['coordinate'])) {
                $validated['coordinate'] = $validated['coordinate'];
            }

            // Update the PAM using the service
            $updatedPam = $this->pamService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'PAM updated successfully!',
                'data' => $updatedPam
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to update PAM: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update PAM: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete PAM.
     */
    public function destroy($id)
    {
        if (!RoleHelper::isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You need superadmin privileges to delete PAM.',
                'error_code' => 'INSUFFICIENT_ROLE'
            ], 403);
        }

        try {
            // Check if PAM exists
            $pam = $this->pamService->findById($id);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            // Check if PAM has related data (areas, customers, etc.)
            if ($this->pamService->hasRelatedData($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete PAM. This PAM has related data (areas, customers, or other records). Please delete related data first.'
                ], 422);
            }

            // Delete the PAM using the service
            $this->pamService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'PAM deleted successfully!'
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to delete PAM: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PAM: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle PAM status (activate/deactivate).
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!RoleHelper::isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You need superadmin privileges to change PAM status.',
                'error_code' => 'INSUFFICIENT_ROLE'
            ], 403);
        }

        try {
            // Check if PAM exists
            $pam = $this->pamService->findById($id);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found'
                ], 404);
            }

            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            // Update only the status
            $updatedPam = $this->pamService->update($id, [
                'is_active' => $validated['is_active']
            ]);

            $statusText = $validated['is_active'] ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "PAM {$statusText} successfully!",
                'data' => $updatedPam
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to toggle PAM status: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to change PAM status: ' . $th->getMessage()
            ], 500);
        }
    }
}
