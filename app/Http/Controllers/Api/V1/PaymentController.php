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
    /**
     * Get payment dashboard data
     */

    public function store(Request $request)
    {
        // return $request->all();
        $bill = Bill::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Bill created successfully',
            'data' => $bill
        ], 201);
    }

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

        $billValidate = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
        ]);

        foreach ($billValidate as $key => $value) {
            $bill = Bill::where('id', $value)->first();
            if (!$bill) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bill not found with ID: ' . $value
                ], 404);
            }
            $bill->status = 'paid';
            $bill->paid_at = Carbon::now()->format('Y-m-d H:i:s');
            $bill->paid_by = $request->user()->id;
            $bill->payment_method = 'cash';
            $bill->save();

            $bill->meterReading->update([
                'status' => 'paid',
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Bill updated successfully',
                'data' => $bill->meterReading
            ], 200);
            $bill->meterReading->update([
                'status' => 'paid',
            ]);
        }

        $bill = Bill::where('customer_id', $customerId)
            ->whereIn('id', $request->bill_ids)
            ->get();
        // $meterReading = $bill->meterReading;
        return response()->json([
            'status' => 'success',
            'message' => 'Bill retrieved successfully',
            'data' => $bill,
        ], 200);

        if (!$bill) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bill not found'
            ], 404);
        }
        $bill->status = 'paid';
        $bill->paid_at = Carbon::now()->format('Y-m-d H:i:s');
        $bill->save();
        $meterReading->status = 'paid';
        $meterReading->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Bill updated successfully',
            'data' => $bill
        ], 200);
    }
}
