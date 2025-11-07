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
     * Delete PAM.
     */
    public function destroyPam($id)
    {
        // PAM deletion logic here

        return back()->with('success', 'PAM deleted successfully');
    }
}
