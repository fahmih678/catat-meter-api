<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\RegisteredMonth;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;

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
            $paymentDataQuery = Bill::select('bills.*', 'customers.name as customer_name', 'customers.customer_number', 'users.name as paid_by_name')
                ->leftJoin('users', 'bills.paid_by', '=', 'users.id')
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
                        'issued_at' => $bill->issued_at
                            ? Carbon::parse($bill->issued_at)->translatedFormat('d M Y')
                            : null,
                        'paid_at' => $bill->paid_at
                            ? Carbon::parse($bill->paid_at)->translatedFormat('d M Y')
                            : null,
                        'paid_by' => $bill->paid_by_name,
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
     * Download payment data for specified period as PDF
     *
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
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
                'customers.name as customer_name',
                'customers.customer_number',
                'users.name as paid_by_name',
                DB::raw("DATE_FORMAT(bills.paid_at, '%d %b %Y') as paid_at_formatted"),
                DB::raw("DATE_FORMAT(bills.issued_at, '%b %Y') as issued_at_formatted"),
            )
                ->leftJoin('customers', 'bills.customer_id', '=', 'customers.id')
                ->leftJoin('users', 'bills.paid_by', '=', 'users.id')
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

            // Calculate summary
            $totalPayments = $paymentData->count();
            $totalAmounts = $paymentData->sum(function ($item) {
                return is_numeric($item->total_bill) ? (float) $item->total_bill : 0;
            });

            // Get PAM information
            $pamName = 'PDAM';
            if (!$isSuperAdmin && $userPamId) {
                $pam = \App\Models\Pam::find($userPamId);
                $pamName = $pam ? $pam->name : 'PDAM';
            }

            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);

            // Create HTML content
            $html = $this->generatePaymentReportHtml($paymentData, $selectedPeriod, $totalPayments, $totalAmounts, $pamName);

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
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payment report: ' . $e->getMessage(),
            ], 500);
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
    private function generatePaymentReportHtml($paymentData, $period, $totalPayments, $totalAmounts, $pamName)
    {
        $periodName = Carbon::createFromFormat('Y-m', $period)->format('F Y');

        $html = view('download_payment_report', [
            'paymentData' => $paymentData,
            'periodName' => $periodName,
            'totalPayments' => $totalPayments,
            'totalAmounts' => $totalAmounts,
            'pamName' => $pamName,
        ])->render();

        return $html;
    }
}
