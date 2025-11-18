<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\RegisteredMonth;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    use HasPamFiltering;

    public function __construct()
    {
        // Apply PAM scope middleware to all methods
        $this->middleware('pam.scope');
    }

    /**
     * Get monthly payment report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyPaymentReport(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validated = $request->validate([
                'period' => 'required|integer|exists:registered_months,id',
            ]);
            // Get selected registered month ID from request, default to current month
            $selectedRegisteredMonth = RegisteredMonth::findOrFail($validated['period']);

            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);

            // Initialize variables
            $selectedPeriod = null;
            $paymentData = collect();
            $summary = [
                'total_payments' => 0,
                'total_amounts' => 0,
            ];

            // Get selected registered month and payment data

            if ($selectedRegisteredMonth) {
                $selectedPeriod = $selectedRegisteredMonth->period;

                // 🔥 Optimized query using registered_month_id directly
                $paymentDataQuery = Bill::select(
                    'bills.id',
                    'bills.bill_number',
                    'bills.total_bill',
                    'bills.payment_method',
                    'bills.issued_at',
                    'bills.paid_at',
                    'bills.status',
                    'customers.name as customer_name',
                    'customers.customer_number',
                    'users.name as paid_by_name',
                )
                    ->join('customers', 'bills.customer_id', '=', 'customers.id')
                    ->leftJoin('users', 'bills.paid_by', '=', 'users.id')
                    ->where('bills.status', 'paid')
                    ->where('bills.registered_month_id', $selectedRegisteredMonth->id)
                    ->whereNull('bills.deleted_at')
                    ->whereNull('customers.deleted_at')
                    ->orderByDesc('bills.paid_at');

                // Apply PAM filtering (non-superadmin only)
                if (!$isSuperAdmin && $userPamId) {
                    $paymentDataQuery->where('bills.pam_id', $userPamId);
                }

                $paymentData = $paymentDataQuery->get()
                    ->map(function ($bill) {
                        return [
                            'bill_id' => $bill->id,
                            'bill_number' => $bill->bill_number,
                            'customer_name' => $bill->customer_name,
                            'customer_number' => $bill->customer_number,
                            'total_bill' => (float) $bill->total_bill,
                            'status' => $bill->status,
                            'payment_method' => $bill->payment_method ?? '-',
                            'issued_at' => Carbon::parse($bill->issued_at)->translatedFormat('d M Y'),
                            'paid_at' => $bill->paid_at
                                ? Carbon::parse($bill->paid_at)->translatedFormat('d M Y')
                                : null,
                            'paid_by' => $bill->paid_by_name,
                        ];
                    });

                // 🔥 Use summary from registered_months table (more efficient)
                $summary = [
                    'total_paid_customers' => (int) $selectedRegisteredMonth->total_paid_customers,
                    'total_payment' => (float) $selectedRegisteredMonth->total_payment,
                ];
            }

            return $this->successResponse([
                'period' => Carbon::parse($selectedPeriod)->translatedFormat('M Y'),
                'summary' => $summary,
                'payment_data' => $paymentData,
            ], 'Laporan pembayaran bulanan berhasil diambil');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Error in monthly payment report', [
                'error_type' => get_class($e),
                'registered_month_id' => $selectedRegisteredMonth->id ?? null,
            ]);
            return $this->errorResponse('Terjadi kesalahan saat mengambil laporan pembayaran bulanan', 500);
        }
    }

    /**
     * Download payment data for specified period as PDF
     *
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function downloadPaymentReport(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        try {
            // Validate query parameters
            $validated = $request->validate([
                'period' => 'required|integer|exists:registered_months,id',
            ]);
            // Get selected period from request, default to current month
            $selectedPeriod = $request->input('period');

            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);

            // Find registered month for the period
            $selectedRegisteredMonth = RegisteredMonth::where('id', $selectedPeriod)
                // ->when(!$isSuperAdmin && $userPamId, function ($query) use ($userPamId) {
                //     return $query->where('pam_id', $userPamId);
                // })
                ->first();

            if (!$selectedRegisteredMonth) {
                return $this->notFoundResponse('Tidak ada periode terdaftar untuk ' . $selectedPeriod);
            }

            // Get payment data using the new table structure (direct join with registered_months)
            $paymentDataQuery = Bill::select(
                'bills.id',
                'bills.bill_number',
                'bills.total_bill',
                'bills.payment_method',
                'bills.paid_at',
                'bills.issued_at',
                'customers.name as customer_name',
                'customers.customer_number',
                'users.name as paid_by_name',
                'registered_months.period',
            )
                ->join('customers', 'bills.customer_id', '=', 'customers.id')
                ->join('registered_months', 'bills.registered_month_id', '=', 'registered_months.id')
                ->leftJoin('users', 'bills.paid_by', '=', 'users.id')
                ->where('bills.status', 'paid')
                ->where('bills.registered_month_id', $selectedRegisteredMonth->id)
                ->whereNull('bills.deleted_at')
                ->whereNull('customers.deleted_at')
                ->whereNull('registered_months.deleted_at')
                ->orderBy('bills.paid_at', 'desc');

            // Apply PAM filtering (already handled by registered_month selection, but for safety)
            if (!$isSuperAdmin && $userPamId) {
                $paymentDataQuery->where('bills.pam_id', $userPamId);
            }

            $paymentData = $paymentDataQuery->get();

            if ($paymentData->isEmpty()) {
                return $this->notFoundResponse('Tidak ada data pembayaran untuk periode ' . $selectedPeriod);
            }

            // 🔥 Use summary from registered_months table (more efficient)
            $totalPayments = (int) $selectedRegisteredMonth->total_paid_customers;
            $totalAmounts = (float) $selectedRegisteredMonth->total_payment;

            // Get PAM information
            $pamName = 'PDAM';
            if (!$isSuperAdmin && $userPamId) {
                $pam = \App\Models\Pam::find($userPamId);
                $pamName = $pam ? $pam->name : 'PDAM';
            } else {
                $pam = \App\Models\Pam::find($selectedRegisteredMonth->pam_id);
                $pamName = $pam ? $pam->name : 'PDAM';
            }

            // Transform payment data for PDF
            $transformedPaymentData = $paymentData->map(function ($item) {
                return (object)[
                    'bill_number' => $item->bill_number,
                    'customer_name' => $item->customer_name,
                    'customer_number' => $item->customer_number,
                    'total_bill' => $item->total_bill,
                    'payment_method' => $item->payment_method ?? '-',
                    'period_formatted' => $item->period,
                    'issued_at_formatted' => $item->issued_at ? Carbon::parse($item->issued_at)->format('d M Y') : '-',
                    'paid_at_formatted' => $item->paid_at ? Carbon::parse($item->paid_at)->format('d M Y') : '-',
                    'paid_by_name' => $item->paid_by_name ?? '-',
                ];
            });
            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);

            // Create HTML content
            $html = $this->generatePaymentReportHtml($transformedPaymentData, $selectedPeriod, $totalPayments, $totalAmounts, $pamName);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Generate filename
            $filename = 'laporan_pembayaran_' . $selectedPeriod . '.pdf';

            // Return PDF response
            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Error generating payment report PDF', [
                'error_type' => get_class($e),
                'period' => $selectedPeriod ?? null,
            ]);
            return $this->errorResponse('Terjadi kesalahan saat membuat laporan pembayaran', 500);
        }
    }

    /**
     * Generate HTML content for PDF payment report
     *
     * @param \Illuminate\Support\Collection $paymentData
     * @param string $period
     * @param int $totalPayments
     * @param float $totalAmounts
     * @param string $pamName
     * @return string
     */
    private function generatePaymentReportHtml(\Illuminate\Support\Collection $paymentData, string $period, int $totalPayments, float $totalAmounts, string $pamName): string
    {
        // Handle different period formats (Y-m vs period ID)
        $periodName = $period;
        if (strlen($period) === 7 && strpos($period, '-') !== false) {
            // Format: Y-m (2024-01)
            try {
                $periodName = Carbon::createFromFormat('Y-m', $period)->format('F Y');
            } catch (\Exception) {
                $periodName = $period;
            }
        } else {
            // Assume it's an ID or other format, get from registered_month
            try {
                $registeredMonth = RegisteredMonth::find($period);
                if ($registeredMonth) {
                    $periodName = Carbon::createFromFormat('Y-m', $registeredMonth->period)->format('F Y');
                }
            } catch (\Exception) {
                $periodName = 'Periode Tidak Diketahui';
            }
        }

        $html = view('download_payment_report', [
            'paymentData' => $paymentData,
            'periodName' => $periodName,
            'totalPayments' => $totalPayments,
            'totalAmounts' => $totalAmounts,
            'pamName' => $pamName,
        ])->render();

        return $html;
    }

    /**
     * Sync payment summary data for specific registered month
     *
     * @param Request $request
     * @param int $registeredMonthId
     * @return JsonResponse
     */
    public function syncPaymentSummaryForMonth(Request $request, int $registeredMonthId): JsonResponse
    {
        try {
            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);

            // Find specific registered month
            $registeredMonthQuery = RegisteredMonth::where('id', $registeredMonthId);

            // Apply PAM filtering for non-superadmin
            if (!$isSuperAdmin && $userPamId) {
                $registeredMonthQuery->where('pam_id', $userPamId);
            }

            $registeredMonth = $registeredMonthQuery->first();

            if (!$registeredMonth) {
                return $this->notFoundResponse('Periode terdaftar tidak ditemukan');
            }

            // Count paid bills for this registered month
            $paidBillsQuery = Bill::where('registered_month_id', $registeredMonth->id)
                ->where('status', 'paid')
                ->whereNull('deleted_at');

            // Apply PAM filtering for additional safety
            if (!$isSuperAdmin && $userPamId) {
                $paidBillsQuery->where('pam_id', $userPamId);
            }

            $paidBills = $paidBillsQuery->get();

            // Calculate actual totals
            $actualTotalPaidCustomers = $paidBills->count();
            $actualTotalPayment = $paidBills->sum('total_bill');

            // Get current values for comparison
            $oldTotalPaidCustomers = $registeredMonth->total_paid_customers ?? 0;
            $oldTotalPayment = $registeredMonth->total_payment ?? 0;

            // Update registered month
            $registeredMonth->update([
                'total_paid_customers' => $actualTotalPaidCustomers,
                'total_payment' => $actualTotalPayment,
            ]);

            return $this->successResponse([
                'period' => Carbon::parse($registeredMonth->period)->translatedFormat('M Y'),
                'summary' => [
                    'old_values' => [
                        'total_paid_customers' => $oldTotalPaidCustomers,
                        'total_payment' => (float) $oldTotalPayment,
                    ],
                    'new_values' => [
                        'total_paid_customers' => $actualTotalPaidCustomers,
                        'total_payment' => $actualTotalPayment,
                    ],
                    'differences' => [
                        'customers_diff' => $actualTotalPaidCustomers - $oldTotalPaidCustomers,
                        'payment_diff' => (float) $actualTotalPayment - (float) $oldTotalPayment,
                    ],
                ],
            ], 'Sinkronisasi data pembayaran periode berhasil dilakukan');
        } catch (\Throwable $e) {
            Log::error('Error in syncPaymentSummaryForMonth', [
                'registered_month_id' => $registeredMonthId,
                'error_type' => get_class($e),
                'user_pam_id' => $request->attributes->get('user_pam_id'),
            ]);

            return $this->errorResponse('Terjadi kesalahan saat sinkronisasi data pembayaran periode', 500);
        }
    }

    /**
     * Get available registered months report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableMonthsPayment(Request $request): JsonResponse
    {
        try {
            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);
            // Get available registered months for user's PAM with enhanced data
            $availableMonthsQuery = RegisteredMonth::select(
                'id',
                'period',
                'status',
                'total_payment',
                'total_paid_customers'
            )
                ->orderBy('period', 'desc');

            // Apply PAM filtering (non-superadmin only)
            if (!$isSuperAdmin && $userPamId) {
                $availableMonthsQuery->where('pam_id', $userPamId);
            }
            $availableMonths = $availableMonthsQuery->get()
                ->map(function ($month) {
                    try {
                        return [
                            'id' => $month->id,
                            'period' => Carbon::parse($month->period)->translatedFormat('M Y'),
                            'status' => $month->status,
                            'total_payment' => (float) $month->total_payment,
                            'total_paid_customers' => (int) $month->total_paid_customers,
                        ];
                    } catch (\Exception $e) {
                        return [
                            'id' => $month->id,
                            'period' => Carbon::parse($month->period)->translatedFormat('M Y'),
                            'status' => $month->status,
                            'total_payment' => (float) $month->total_payment,
                            'total_paid_customers' => (int) $month->total_paid_customers,
                        ];
                    }
                });

            return $this->successResponse(['available_months' => $availableMonths], 'Available period of payment retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil daftar bulan aktif', 500);
        }
    }
}
