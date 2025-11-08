<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Customer;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AreaController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Display areas for a specific PAM.
     */
    public function index($pamId)
    {
        try {
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return back()->with('error', 'PAM not found');
            }

            $areas = Area::where('pam_id', $pamId)
                ->orderBy('name')
                ->get();

            return view('dashboard.pam.partials.detail-area', compact('pam', 'areas'));
        } catch (\Exception $e) {
            Log::error('Areas retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to retrieve areas. Please try again.');
        }
    }

    /**
     * Store a new area for a specific PAM.
     */
    public function store(Request $request, $pamId)
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
            $existingArea = Area::where('pam_id', $pamId)
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
            $area = Area::create($validated);

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
     * Get area data for editing.
     */
    public function edit($pamId, $id)
    {
        try {
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return response()->json([
                    'success' => false,
                    'message' => 'PAM not found',
                    'errors' => []
                ], 404);
            }

            $area = Area::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area not found',
                    'errors' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $area,
                'message' => 'Area data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Area edit data retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'area_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve area data. Please try again.',
                'errors' => []
            ], 500);
        }
    }

    /**
     * Update an area within a specific PAM.
     */
    public function update(Request $request, $pamId, $id)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:areas,code,' . $id . ',id,pam_id,' . $pamId,
                'description' => 'nullable|string|max:1000',
            ]);

            // Verify PAM exists
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                $errorMessage = 'PAM not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Find the area
            $area = Area::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$area) {
                $errorMessage = 'Area not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Update the area
            $area->update($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Area updated successfully',
                    'data' => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'code' => $area->code,
                        'description' => $area->description,
                        'updated_at' => $area->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return back()->with('success', 'Area updated successfully');
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
            // Log the error for debugging
            Log::error('Area update error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'area_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update area. Please try again.';

            // Return error response for AJAX requests
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
     * Delete an area within a specific PAM.
     */
    public function destroy(Request $request, $pamId, $id)
    {
        try {
            // Verify PAM exists
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                $errorMessage = 'PAM not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Find the area
            $area = Area::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$area) {
                $errorMessage = 'Area not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Check if area has customers (optional business rule)
            $customerCount = Customer::where('area_id', $id)->count();
            if ($customerCount > 0) {
                $errorMessage = "Cannot delete area with {$customerCount} associated customers";
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Delete the area
            $areaName = $area->name;
            $area->delete();

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Area deleted successfully',
                    'data' => [
                        'deleted_area_id' => $area->id,
                        'deleted_area_name' => $areaName
                    ]
                ]);
            }

            return back()->with('success', 'Area deleted successfully');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Area deletion error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'area_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete area. Please try again.';

            // Return error response for AJAX requests
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
}
