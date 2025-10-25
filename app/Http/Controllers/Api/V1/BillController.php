<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\RegisteredMonth;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BillController extends Controller
{
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
            // Get selected period from request, default to current month
            $selectedPeriod = $request->input('period', Carbon::now()->format('Y-m'));

            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);

            // Get available registered months for user's PAM
            $availableMonthsQuery = RegisteredMonth::select('period', 'status')
                ->distinct()
                ->orderBy('period', 'desc');

            // Apply PAM filtering (non-superadmin only)
            if (!$isSuperAdmin && $userPamId) {
                $availableMonthsQuery->where('pam_id', $userPamId);
            }
            $availableMonths = $availableMonthsQuery->get()
                ->map(function ($month) {
                    try {
                        return [
                            'period' => $month->period,
                            'month_name' => Carbon::createFromFormat('Y-m', $month->period)->format('F Y'),
                            'status' => $month->status,
                        ];
                    } catch (\Exception) {
                        return [
                            'period' => $month->period,
                            'month_name' => $month->period,
                            'status' => $month->status,
                        ];
                    }
                });

            // Get payment data for selected period (paid bills only)
            $paymentDataQuery = Bill::select('bills.*', 'customers.name as customer_name', 'customers.customer_number')
                ->leftJoin('customers', 'bills.customer_id', '=', 'customers.id')
                ->where('bills.status', 'paid')
                ->when($selectedPeriod, function ($query, $period) {
                    $year = substr($period, 0, 4);
                    $month = substr($period, 5, 2);
                    return $query->whereMonth('bills.paid_at', $month)
                        ->whereYear('bills.paid_at', $year);
                })
                ->orderBy('bills.paid_at', 'desc');

            // Apply PAM filtering (non-superadmin only)
            if (!$isSuperAdmin && $userPamId) {
                $paymentDataQuery->where('bills.pam_id', $userPamId);
            }
            $paymentData = $paymentDataQuery->get()
                ->map(function ($bill) {
                    return [
                        'bill_id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'customer_name' => $bill->customer_name ?? '',
                        'customer_number' => $bill->customer_number ?? '',
                        'total_bill' => is_numeric($bill->total_bill) ? (float) $bill->total_bill : 0,
                        'status' => $bill->status === 'paid' ? 1 : 0,
                        'payment_method' => $bill->payment_method,
                        'issued_at' => $bill->issued_at ? date('Y-m-d H:i:s', strtotime($bill->issued_at)) : null,
                        'paid_at' => $bill->paid_at ? date('Y-m-d H:i:s', strtotime($bill->paid_at)) : null,
                    ];
                });

            // Calculate summary
            $summary = [
                'total_payments' => $paymentData->count(),
                'total_amounts' => (float) $paymentData->sum(function ($item) {
                    return is_numeric($item['total_bill']) ? $item['total_bill'] : 0;
                }),
            ];

            $response = [
                'available_registered_months' => $availableMonths,
                'period' => $selectedPeriod,
                'payment_data' => $paymentData,
                'summary' => $summary,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Laporan pembayaran bulanan berhasil diambil',
                'data' => $response,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download payment data for specified period
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadPaymentReport(Request $request)
    {
        try {
            // Get selected period from request, default to current month
            $selectedPeriod = $request->input('period', Carbon::now()->format('Y-m'));

            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);

            // Get payment data for selected period (paid bills only)
            $paymentDataQuery = Bill::select(
                    'bills.id',
                    'bills.bill_number',
                    'bills.total_bill',
                    'bills.payment_method',
                    'bills.paid_at',
                    'bills.issued_at',
                    'customers.name as customer_name',
                    'customers.customer_number'
                )
                ->leftJoin('customers', 'bills.customer_id', '=', 'customers.id')
                ->where('bills.status', 'paid')
                ->when($selectedPeriod, function ($query, $period) {
                    $year = substr($period, 0, 4);
                    $month = substr($period, 5, 2);
                    return $query->whereMonth('bills.paid_at', $month)
                                 ->whereYear('bills.paid_at', $year);
                })
                ->orderBy('bills.paid_at', 'desc');

            // Apply PAM filtering (non-superadmin only)
            if (!$isSuperAdmin && $userPamId) {
                $paymentDataQuery->where('bills.pam_id', $userPamId);
            }

            $paymentData = $paymentDataQuery->get();

            if ($paymentData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data pembayaran untuk periode ' . $selectedPeriod,
                ], 404);
            }

            // Prepare CSV data
            $csvFileName = 'laporan_pembayaran_' . $selectedPeriod . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
            ];

            $callback = function () use ($paymentData) {
                $file = fopen('php://output', 'w');

                // Add UTF-8 BOM for proper Excel compatibility
                fwrite($file, "\xEF\xBB\xBF");

                // CSV Header
                fputcsv($file, [
                    'ID',
                    'Nomor Bill',
                    'Nama Pelanggan',
                    'Nomor Pelanggan',
                    'Total Tagihan',
                    'Metode Pembayaran',
                    'Tanggal Diterbitkan',
                    'Tanggal Dibayar'
                ]);

                // CSV Data
                foreach ($paymentData as $payment) {
                    fputcsv($file, [
                        $payment->id,
                        $payment->bill_number,
                        $payment->customer_name ?? '',
                        $payment->customer_number ?? '',
                        number_format($payment->total_bill, 2, ',', '.'),
                        $payment->payment_method ?? '',
                        $payment->issued_at ? date('d/m/Y H:i:s', strtotime($payment->issued_at)) : '',
                        $payment->paid_at ? date('d/m/Y H:i:s', strtotime($payment->paid_at)) : '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payment report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
