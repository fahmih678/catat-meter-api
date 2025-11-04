<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\FixedFee;
use App\Services\PamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FixedFeeController extends Controller
{
    private PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Store a new fixed fee for a specific PAM.
     */
    public function store(Request $request, $pamId)
    {
        try {
            $validated = $request->validate([
                'tariff_group_id' => 'required|exists:tariff_groups,id',
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'effective_from' => 'required|date',
                'effective_to' => 'nullable|date|after_or_equal:effective_from',
                'description' => 'nullable|string',
                'is_active' => 'required|boolean',
            ]);

            // Add pam_id to validated data
            $validated['pam_id'] = $pamId;

            // Create the fixed fee
            $fixedFee = FixedFee::create($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fixed fee created successfully!',
                    'data' => [
                        'id' => $fixedFee->id,
                        'name' => $fixedFee->name,
                        'amount' => $fixedFee->amount,
                        'description' => $fixedFee->description,
                        'is_active' => $fixedFee->is_active,
                        'pam_id' => $fixedFee->pam_id,
                        'created_at' => $fixedFee->created_at->format('Y-m-d H:i:s')
                    ]
                ], 201);
            }

            return back()->with('success', 'Fixed fee created successfully');
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
            Log::error('Failed to create fixed fee: ' . $th->getMessage());

            // Return JSON error response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create fixed fee: ' . $th->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to create fixed fee: ' . $th->getMessage()])->withInput();
        }
    }

    /**
     * Get fixed fee data for editing.
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

            $fixedFee = FixedFee::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$fixedFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fixed fee not found',
                    'errors' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $fixedFee,
                'message' => 'Fixed fee data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Fixed fee edit data retrieval error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'fixed_fee_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fixed fee data. Please try again.',
                'errors' => []
            ], 500);
        }
    }

    /**
     * Update a fixed fee within a specific PAM.
     */
    public function update(Request $request, $pamId, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'tariff_group_id' => 'required|exists:tariff_groups,id',
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

            // Find the fixed fee
            $fixedFee = FixedFee::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$fixedFee) {
                $errorMessage = 'Fixed fee not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Update the fixed fee
            $fixedFee->update($validated);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fixed fee updated successfully',
                    'data' => [
                        'id' => $fixedFee->id,
                        'name' => $fixedFee->name,
                        'code' => $fixedFee->code,
                        'amount' => $fixedFee->amount,
                        'frequency' => $fixedFee->frequency,
                        'description' => $fixedFee->description,
                        'is_active' => $fixedFee->is_active,
                        'updated_at' => $fixedFee->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return back()->with('success', 'Fixed fee updated successfully');
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
            Log::error('Fixed fee update error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'fixed_fee_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to update fixed fee. Please try again.';

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
     * Delete a fixed fee within a specific PAM.
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

            // Find the fixed fee
            $fixedFee = FixedFee::where('pam_id', $pamId)
                ->where('id', $id)
                ->first();

            if (!$fixedFee) {
                $errorMessage = 'Fixed fee not found';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => []
                    ], 404);
                }
                return back()->with('error', $errorMessage);
            }

            // Delete the fixed fee
            $fixedFeeName = $fixedFee->name;
            $fixedFee->delete();

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fixed fee deleted successfully',
                    'data' => [
                        'deleted_fixed_fee_id' => $fixedFee->id,
                        'deleted_fixed_fee_name' => $fixedFeeName
                    ]
                ]);
            }

            return back()->with('success', 'Fixed fee deleted successfully');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Fixed fee deletion error: ' . $e->getMessage(), [
                'pam_id' => $pamId,
                'fixed_fee_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to delete fixed fee. Please try again.';

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
