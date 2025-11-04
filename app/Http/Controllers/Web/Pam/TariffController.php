<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\TariffGroup;
use App\Models\TariffTier;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TariffController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Display tariff groups for a specific PAM.
     */
    public function groups($pamId)
    {
        try {
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return back()->with('error', 'PAM not found');
            }

            $tariffGroups = TariffGroup::select('id', 'name', 'is_active', 'description')
                ->withCount('customers')
                ->withCount('tariffTiers')
                ->where('pam_id', $pamId)
                ->get();

            return view('dashboard.pam.partials.detail-tariff-group', compact('pam', 'tariffGroups'));
        } catch (\Exception $e) {
            Log::error('Tariff groups retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to retrieve tariff groups. Please try again.');
        }
    }

    /**
     * Store a new tariff group for a specific PAM.
     */
    public function storeGroup(Request $request, $pamId)
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

            // Create the tariff group
            $tariffGroup = TariffGroup::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff group created successfully!',
                    'data' => [
                        'id' => $tariffGroup->id,
                        'name' => $tariffGroup->name,
                        'description' => $tariffGroup->description,
                        'is_active' => $tariffGroup->is_active,
                        'pam_id' => $tariffGroup->pam_id,
                        'created_at' => $tariffGroup->created_at->format('Y-m-d H:i:s')
                    ]
                ], 201);
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
     * Get tariff group data for editing.
     */
    public function editGroup($pamId, $id)
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

            $tariffGroup = TariffGroup::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$tariffGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tariff group not found',
                    'errors' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tariffGroup,
                'message' => 'Tariff group data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Tariff group edit data retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_group_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tariff group data. Please try again.',
                'errors' => []
            ], 500);
        }
    }

    /**
     * Update a tariff group within a specific PAM.
     */
    public function updateGroup(Request $request, $pamId, $id)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'is_active' => 'required|boolean'
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

            // Find the tariff group
            $tariffGroup = TariffGroup::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$tariffGroup) {
                $errorMessage = 'Tariff group not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Update the tariff group
            $tariffGroup->update($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff group updated successfully',
                    'data' => [
                        'id' => $tariffGroup->id,
                        'name' => $tariffGroup->name,
                        'description' => $tariffGroup->description,
                        'is_active' => $tariffGroup->is_active,
                        'updated_at' => $tariffGroup->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return back()->with('success', 'Tariff group updated successfully');
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
            Log::error('Tariff group update error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_group_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update tariff group. Please try again.';

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
     * Delete a tariff group within a specific PAM.
     */
    public function destroyGroup(Request $request, $pamId, $id)
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

            // Find the tariff group
            $tariffGroup = TariffGroup::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$tariffGroup) {
                $errorMessage = 'Tariff group not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Check if tariff group has tiers (optional business rule)
            $tierCount = TariffTier::where('tariff_group_id', $id)->count();
            if ($tierCount > 0) {
                $errorMessage = "Cannot delete tariff group with {$tierCount} associated tariff tiers";
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Delete the tariff group
            $tariffGroupName = $tariffGroup->name;
            $tariffGroup->delete();

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff group deleted successfully',
                    'data' => [
                        'deleted_tariff_group_id' => $tariffGroup->id,
                        'deleted_tariff_group_name' => $tariffGroupName
                    ]
                ]);
            }

            return back()->with('success', 'Tariff group deleted successfully');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Tariff group deletion error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_group_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete tariff group. Please try again.';

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
     * Display tariff tiers for a specific PAM.
     */
    public function tiers($pamId)
    {
        try {
            $pam = $this->pamService->findById($pamId);
            if (!$pam) {
                return back()->with('error', 'PAM not found');
            }

            $tariffTiers = TariffTier::select('description', 'meter_min', 'meter_max', 'amount', 'is_active', 'effective_from', 'effective_to', 'tariff_group_id')
                ->with(['tariffGroup:id,name'])
                ->where('pam_id', $pamId)
                ->get();

            return view('dashboard.pam.partials.detail-tariff-tier', compact('pam', 'tariffTiers'));
        } catch (\Exception $e) {
            Log::error('Tariff tiers retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to retrieve tariff tiers. Please try again.');
        }
    }

    /**
     * Store a new tariff tier for a specific PAM.
     */
    public function storeTier(Request $request, $pamId)
    {
        try {
            $validated = $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'name' => 'required|string|max:255',
                'min_meter' => 'required|numeric|min:0',
                'max_meter' => 'nullable|numeric|gt:min_meter',
                'price_per_m3' => 'required|numeric|min:0',
                'status' => 'required|in:active,inactive',
            ]);

            // Add pam_id to validated data
            $validated['pam_id'] = $pamId;

            // Create the tariff tier
            $tariffTier = TariffTier::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff tier created successfully!',
                    'data' => $tariffTier
                ], 201);
            }

            return back()->with('success', 'Tariff tier created successfully');
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
            Log::error('Failed to create tariff tier: ' . $th->getMessage());

            // Return JSON error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create tariff tier: ' . $th->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to create tariff tier: ' . $th->getMessage()])->withInput();
        }
    }

    /**
     * Update a tariff tier within a specific PAM.
     */
    public function updateTier(Request $request, $pamId, $id)
    {
        try {
            $validated = $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'name' => 'required|string|max:255',
                'min_meter' => 'required|numeric|min:0',
                'max_meter' => 'nullable|numeric|gt:min_meter',
                'price_per_m3' => 'required|numeric|min:0',
                'status' => 'required|in:active,inactive',
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

            // Find the tariff tier
            $tariffTier = TariffTier::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$tariffTier) {
                $errorMessage = 'Tariff tier not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Update the tariff tier
            $tariffTier->update($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff tier updated successfully',
                    'data' => $tariffTier
                ]);
            }

            return back()->with('success', 'Tariff tier updated successfully');
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
            Log::error('Tariff tier update error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tier_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update tariff tier. Please try again.';

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
     * Delete a tariff tier within a specific PAM.
     */
    public function destroyTier(Request $request, $pamId, $id)
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

            // Find the tariff tier
            $tariffTier = TariffTier::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$tariffTier) {
                $errorMessage = 'Tariff tier not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Delete the tariff tier
            $tariffTierName = $tariffTier->description;
            $tariffTier->delete();

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff tier deleted successfully',
                    'data' => [
                        'deleted_tier_id' => $tariffTier->id,
                        'deleted_tier_name' => $tariffTierName
                    ]
                ]);
            }

            return back()->with('success', 'Tariff tier deleted successfully');
        } catch (\Exception $e) {
            Log::error('Tariff tier deletion error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tier_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete tariff tier. Please try again.';

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
