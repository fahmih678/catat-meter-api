<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\TariffTier;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TariffTierController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Store a new tariff tier for a specific PAM.
     */
    public function store(Request $request, $pamId)
    {
        try {
            $validated = $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'meter_min' => 'required|numeric|min:0',
                'meter_max' => 'required|numeric|min:0|gt:meter_min',
                'amount' => 'required|numeric|min:0',
                'effective_from' => 'required|date',
                'effective_to' => 'nullable|date|after_or_equal:effective_from',
                'description' => 'nullable|string',
                'is_active' => 'required|boolean',
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
                    'data' => [
                        'id' => $tariffTier->id,
                        'tariff_group_id' => $tariffTier->tariff_group_id,
                        'meter_min' => $tariffTier->meter_min,
                        'meter_max' => $tariffTier->meter_max,
                        'amount' => $tariffTier->amount,
                        'effective_from' => $tariffTier->effective_from->format('Y-m-d'),
                        'effective_to' => $tariffTier->effective_to ? $tariffTier->effective_to->format('Y-m-d') : null,
                        'description' => $tariffTier->description,
                        'is_active' => $tariffTier->is_active,
                        'pam_id' => $tariffTier->pam_id,
                        'created_at' => $tariffTier->created_at->format('Y-m-d H:i:s')
                    ]
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
     * Get tariff tier data for editing.
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

            $tariffTier = TariffTier::where('pam_id', $pamId)
                ->where('id', $id)
                ->with('tariffGroup')
                ->first();

            if (!$tariffTier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tariff tier not found',
                    'errors' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tariffTier,
                'message' => 'Tariff tier data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Tariff tier edit data retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_tier_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tariff tier data. Please try again.',
                'errors' => []
            ], 500);
        }
    }

    /**
     * Update a tariff tier within a specific PAM.
     */
    public function update(Request $request, $pamId, $id)
    {
        try {
            $validated = $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'meter_min' => 'required|numeric|min:0',
                'meter_max' => 'required|numeric|min:0|gt:meter_min',
                'amount' => 'required|numeric|min:0',
                'effective_from' => 'required|date',
                'effective_to' => 'nullable|date|after_or_equal:effective_from',
                'description' => 'nullable|string',
                'is_active' => 'required|boolean',
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
                    'data' => [
                        'id' => $tariffTier->id,
                        'tariff_group_id' => $tariffTier->tariff_group_id,
                        'meter_min' => $tariffTier->meter_min,
                        'meter_max' => $tariffTier->meter_max,
                        'amount' => $tariffTier->amount,
                        'effective_from' => $tariffTier->effective_from->format('Y-m-d'),
                        'effective_to' => $tariffTier->effective_to ? $tariffTier->effective_to->format('Y-m-d') : null,
                        'description' => $tariffTier->description,
                        'is_active' => $tariffTier->is_active,
                        'updated_at' => $tariffTier->updated_at->format('Y-m-d H:i:s')
                    ]
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
            // Log the error for debugging
            Log::error('Tariff tier update error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_tier_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update tariff tier. Please try again.';

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
     * Delete a tariff tier within a specific PAM.
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

            // Find the tariff tier
            $tariffTier = TariffTier::where('pam_id', $pamId)
                ->where('id', $id)
                ->with('tariffGroup')
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

            // Store tier info for response
            $tariffTierName = $tariffTier->tariffGroup->name ?? 'Tariff Tier';

            // Delete the tariff tier
            $tariffTier->delete();

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tariff tier deleted successfully',
                    'data' => [
                        'deleted_tariff_tier_id' => $tariffTier->id,
                        'deleted_tariff_tier_name' => $tariffTierName
                    ]
                ]);
            }

            return back()->with('success', 'Tariff tier deleted successfully');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Tariff tier deletion error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'tariff_tier_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete tariff tier. Please try again.';

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
