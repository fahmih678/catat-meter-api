<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Meter;
use App\Models\Bill;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use HasPamFiltering;

    public function getBilling($customerId): JsonResponse
    {
        try {
            // Get bills with customer, meter, area and tariff information
            $bills = Bill::with([
                'customer.tariffGroup.tariffTiers',
                'customer.tariffGroup.fixedFees',
                'customer.meter',
                'customer.area',
                'meterReading'
            ])
                ->where('status', 'pending')
                ->where('customer_id', $customerId)->get();

            if ($bills->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No bills found for the given customer ID'
                ], 404);
            }

            // Get customer data (since all bills belong to same customer)
            $customer = $bills->first()->customer;

            // Format the response data
            $responseData = [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'number' => $customer->customer_number
                ],
                'meter_number' => $customer->meter ? $customer->meter->meter_number : null,
                'area' => $customer->area ? $customer->area->name : null,
                'tagihan' => $bills->map(function ($bill) use ($customer) {
                    return [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'period' => $bill->meterReading ? $bill->meterReading->registeredMonth->period : null,
                        'due_date' => $bill->due_date ? $bill->due_date->format('Y-m-d') : null,
                        'volume_usage' => $bill->volume_usage,
                        'total_bill' => $bill->total_bill,
                        'tariff_snapshot' => json_decode($bill->tariff_snapshot, true),
                    ];
                })
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Bills retrieved successfully',
                'data' => $responseData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving bills: ' . $e->getMessage()
            ], 500);
        }
    }

    public function payBilling(Request $request, int $customerId): JsonResponse
    {
        $validatedData = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
        ]);

        $billIds = $validatedData['bill_ids'];
        $updatedBills = [];
        $errors = [];

        // Verify bills belong to the specified customer
        $bills = Bill::where('customer_id', $customerId)
            ->whereIn('id', $billIds)
            ->where('status', 'pending')
            ->with('meterReading')
            ->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending bills found for the given customer ID and bill IDs'
            ], 404);
        }

        // Check if all requested bills exist and belong to the customer
        $foundBillIds = $bills->pluck('id')->toArray();
        $missingBillIds = array_diff($billIds, $foundBillIds);

        if (!empty($missingBillIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some bills not found or already paid: ' . implode(', ', $missingBillIds)
            ], 404);
        }

        // Update each bill
        foreach ($bills as $bill) {
            try {
                $bill->status = 'paid';
                $bill->paid_at = Carbon::now()->format('Y-m-d H:i:s');
                $bill->paid_by = $request->user()->id;
                $bill->payment_method = 'cash';
                $bill->save();

                // Update meter reading status if exists
                if ($bill->meterReading) {
                    $bill->meterReading->update([
                        'status' => 'paid',
                    ]);
                }

                $updatedBills[] = [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'total_bill' => $bill->total_bill,
                    'paid_at' => $bill->paid_at
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'bill_id' => $bill->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'status' => 'partial_success',
                'message' => 'Some bills were updated with errors',
                'updated_bills' => $updatedBills,
                'errors' => $errors
            ], 207); // 207 Multi-Status
        }

        return response()->json([
            'status' => 'success',
            'message' => count($updatedBills) . ' bills paid successfully',
            'data' => [
                'updated_bills' => $updatedBills,
                'total_amount' => $bills->sum('total_bill'),
                'customer_id' => $customerId
            ]
        ], 200);
    }
}
