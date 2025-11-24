<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Bill;
use App\Models\RegisteredMonth;
use Carbon\Carbon;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use HasPamFiltering;

    /**
     * Get all pending bills for a customer
     *
     * @param int $customerId
     * @return JsonResponse
     */
    public function getBills($customerId): JsonResponse
    {
        try {
            // Check if user can access billing features
            if (!RoleHelper::canAccessBilling()) {
                return $this->forbiddenResponse('Access denied. You do not have permission to access billing features.');
            }

            $customer = Customer::findOrFail($customerId);

            // Check PAM access for the customer
            $pamAccess = $this->checkEntityPamAccess($customer);
            if ($pamAccess) {
                return $pamAccess;
            }

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
                return $this->notFoundResponse('No bills found for the given customer ID');
            }

            // Format the response data
            $responseData = [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'number' => $customer->customer_number
                ],
                'meter_number' => $customer->meter ? $customer->meter->meter_number : null,
                'area' => $customer->area ? $customer->area->name : null,
                'bill' => $bills->map(function ($bill) use ($customer) {
                    return [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'reading_period' => Carbon::parse($bill->meterReading->registeredMonth->period)->format('M Y'),
                        'payment_period' => $bill->registeredMonth ? Carbon::parse($bill->due_date)->format('M Y') : null,
                        'due_date' =>  Carbon::parse($bill->due_date)->format('d M Y'),
                        'volume_usage' => $bill->volume_usage,
                        'total_bill' => (float) $bill->total_bill,
                        'tariff_snapshot' => json_decode($bill->tariff_snapshot, true),
                    ];
                })
            ];

            return $this->successResponse($responseData, 'Bills retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil data tagihan', 500);
        }
    }

    /**
     * Process payment for multiple bills of a customer
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function payBills(Request $request, int $customerId): JsonResponse
    {
        try {
            // Check if user can access billing features
            if (!RoleHelper::canAccessBilling()) {
                return $this->forbiddenResponse('Access denied. You do not have permission to access billing features.');
            }

            $validatedData = $request->validate([
                'bill_ids' => 'required|array|min:1',
                'bill_ids.*' => 'integer|exists:bills,id',
                'payment_method' => 'required|in:cash,transfer,ewallet',
            ]);

            // Check if customer exists
            $customer = Customer::findOrFail($customerId);

            // Check PAM access for the customer
            $pamAccess = $this->checkEntityPamAccess($customer);
            if ($pamAccess) {
                return $pamAccess;
            }

            $billIds = $validatedData['bill_ids'];
            $paymentMethod = $validatedData['payment_method'] ?? 'cash';

            // Verify bills belong to the specified customer
            $bills = Bill::where('customer_id', $customerId)
                ->whereIn('id', $billIds)
                ->where('status', 'pending')
                ->with('meterReading')
                ->lockForUpdate() // Prevent race conditions
                ->get();

            if ($bills->isEmpty()) {
                return $this->notFoundResponse('No pending bills found for the given customer ID and bill IDs');
            }

            // Check if all requested bills exist and belong to the customer
            $foundBillIds = $bills->pluck('id')->toArray();
            $missingBillIds = array_diff($billIds, $foundBillIds);

            if (!empty($missingBillIds)) {
                return $this->notFoundResponse('Some bills not found or already paid: ' . implode(', ', $missingBillIds));
            }

            $paymentTime = Carbon::now();
            $registeredMonth = RegisteredMonth::where('pam_id', $customer->pam_id)
                ->whereYear('period', $paymentTime->year)
                ->whereMonth('period', $paymentTime->month)
                ->first();

            if (!$registeredMonth) {
                return $this->errorResponse('Registered month tidak ditemukan', 404);
            }

            // Start database transaction for data consistency
            return DB::transaction(function () use ($bills, $paymentTime, $registeredMonth, $paymentMethod, $request, $customer) {
                $updatedBills = [];
                $totalPayment = 0;
                $errors = [];

                // Update each bill within transaction
                foreach ($bills as $bill) {
                    try {
                        $bill->update([
                            'registered_month_id' => $registeredMonth->id,
                            'paid_at' => $paymentTime,
                            'status' => 'paid',
                            'paid_by' => $request->user()->id,
                            'payment_method' => $paymentMethod,
                        ]);

                        if ($bill->meterReading) {
                            $bill->meterReading->update([
                                'status' => 'paid',
                            ]);
                        }

                        $updatedBills[] = [
                            'bill_number' => $bill->bill_number,
                            'total_bill' => (float) $bill->total_bill,
                        ];
                        $totalPayment += (float) $bill->total_bill;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'bill_id' => $bill->id,
                            'error' => 'Terjadi kesalahan internal saat memproses tagihan'
                        ];
                    }
                }

                // 🔥 Hitung total payment SEKALI SAJA (efisien)
                $newTotalPayment = $registeredMonth->bills()
                    ->where('status', 'paid')
                    ->selectRaw('COALESCE(SUM(total_bill), 0) as total_payment, COUNT(*) as total_paid_customers')
                    ->first();

                // 🔥 Update registeredMonth sekali saja
                $registeredMonth->update([
                    'total_payment' => $newTotalPayment->total_payment,
                    'total_paid_customers' => $newTotalPayment->total_paid_customers,
                ]);

                if (!empty($errors)) {
                    // Transaction will be rolled back automatically when exception is thrown
                    throw new \Exception('Some bills failed to update');
                }

                return $this->successResponse([
                    'customer_name' => $customer->name,
                    'total_payment' => $totalPayment,
                    'paid_at' => $paymentTime->format('Y-m-d H:i:s'),
                    'updated_bills' => $updatedBills,
                ], count($updatedBills) . ' bills paid successfully');
            });
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat memproses pembayaran', 500);
        }
    }

    /**
     * Remove a paid bill (refund/reversal)
     *
     * @param int $billId
     * @return JsonResponse
     */
    public function destroy(int $billId): JsonResponse
    {
        try {
            // Check if user can access billing features
            if (!RoleHelper::canAccessBilling()) {
                return $this->forbiddenResponse('Access denied. You do not have permission to access billing features.');
            }

            $bill = Bill::with(['meterReading', 'customer'])->findOrFail($billId);

            if ($bill->status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only paid bills can be removed'
                ], 400);
            }

            // Check PAM access for the customer associated with this bill
            $pamAccess = $this->checkEntityPamAccess($bill->customer);
            if ($pamAccess) {
                return $pamAccess;
            }

            // Store bill info for registered month update
            $periodDate = Carbon::parse($bill->paid_at);
            $registeredMonth = RegisteredMonth::where('pam_id', $bill->pam_id)
                ->whereYear('period', $periodDate->year)
                ->whereMonth('period', $periodDate->month)
                ->first();

            // Update meter reading status to pending if exists
            if ($bill->meterReading) {
                $bill->meterReading->update([
                    'status' => 'draft',
                ]);
            }

            $bill->forceDelete();

            // 🔥 Recalculate total payment seperti di payBills (efisien dan konsisten)
            if ($registeredMonth) {
                $newTotalPayment = $registeredMonth->bills()
                    ->where('status', 'paid')
                    ->selectRaw('COALESCE(SUM(total_bill), 0) as total_payment, COUNT(*) as total_paid_customers')
                    ->first();

                // 🔥 Update registeredMonth sekali saja
                $registeredMonth->update([
                    'total_payment' => $newTotalPayment->total_payment,
                    'total_paid_customers' => $newTotalPayment->total_paid_customers,
                ]);
            }

            return $this->successResponse([
                'total_paid_customers' => $registeredMonth->total_paid_customers,
                'total_payment' => (float) $registeredMonth->total_payment,
            ], 'Bill removed successfully, meter reading status updated to draft, and registered month totals updated');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Bill not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat menghapus tagihan', 500);
        }
    }
}
