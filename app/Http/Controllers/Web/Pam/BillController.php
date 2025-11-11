<?php

namespace App\Http\Controllers\Web\Pam;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Pam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $pamId)
    {
        $pam = Pam::findOrFail($pamId);

        // Get distinct months from paid_at bills
        $paidMonths = Bill::whereNotNull('paid_at')
            ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month_year, DATE_FORMAT(paid_at, "%M %Y") as month_year_name')
            ->distinct()
            ->orderBy('month_year', 'desc')
            ->pluck('month_year_name', 'month_year');

        // Get distinct users who received payments
        $paidUsersQuery = Bill::whereNotNull('paid_by')
            ->with('paidBy')
            ->select('paid_by')
            ->distinct()
            ->get();

        $paidUsers = $paidUsersQuery->pluck('paidBy')->unique('id')->sortBy('name');

        // Build bills query with filters
        $billsQuery = Bill::with(['customer', 'pam', 'meterReading', 'paidBy'])
            ->where('pam_id', $pamId);

        // Always filter by month - default to current month if not specified
        $selectedMonth = $request->get('month', now()->format('Y-m'));

        // Always apply month filter
        $billsQuery->whereRaw('DATE_FORMAT(paid_at, "%Y-%m") = ?', [$selectedMonth]);

        // Apply user filter
        if ($request->get('user')) {
            $billsQuery->where('paid_by', $request->get('user'));
        }

        // Apply search filter if provided
        $search = $request->get('search');
        if ($search) {
            $billsQuery->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', '%' . $search . '%')
                    ->orWhere('reference_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('customer_number', 'like', '%' . $search . '%');
                    });
            });
        }

        // Order and paginate
        $bills = $billsQuery->orderBy('paid_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('dashboard.pam.bills.index', compact('pam', 'bills', 'paidMonths', 'paidUsers', 'search', 'selectedMonth'));
    }

    /**
     * Delete bill - change meter reading status to draft and delete bill
     */
    public function deleteBill(Request $request, $pamId, $billId)
    {
        try {
            // Find bill with relationships
            $bill = Bill::with(['meterReading', 'customer'])->where('id', $billId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                // Update meter reading status to draft before deleting bill
                if ($bill->meterReading) {
                    $bill->meterReading->update(['status' => 'draft']);
                }

                // Store bill info for logging
                $billInfo = [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'customer_name' => $bill->customer->name ?? '-'
                ];

                // Delete the bill
                $bill->delete();

                Log::info("Bill {$billId} deleted - meter reading status changed to draft");

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan berhasil dihapus, status pembacaan meter diubah menjadi Draft',
                    'data' => $billInfo
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Bill not found for delete: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan'
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to delete bill: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus tagihan'
            ], 500);
        }
    }

    /**
     * Download payment data for specified period as PDF
     */
    public function downloadPaymentReport(Request $request, $pamId)
    {
        try {
            // Validate that PAM exists
            $pam = Pam::findOrFail($pamId);

            // Get selected period from request, default to current month
            $selectedPeriod = $request->input('period', now()->format('Y-m'));
            $selectedUser = $request->input('user');

            // Get payment data for selected period (paid bills only)
            $paymentDataQuery = Bill::select(
                'bills.id',
                'bills.bill_number',
                'bills.total_bill',
                'bills.payment_method',
                'bills.issued_at',
                'bills.paid_at',
                'customers.name as customer_name',
                'customers.customer_number',
                'users.name as paid_by_name',
                DB::raw("DATE_FORMAT(registered_months.period, '%b %Y') as period_formatted"),
                DB::raw("DATE_FORMAT(bills.issued_at, '%d %b %Y') as issued_at_formatted"),
                DB::raw("DATE_FORMAT(bills.paid_at, '%d %b %Y') as paid_at_formatted"),
            )
                ->leftJoin('customers', 'bills.customer_id', '=', 'customers.id')
                ->join('meter_readings', 'bills.meter_reading_id', '=', 'meter_readings.id')
                ->join('registered_months', 'meter_readings.registered_month_id', '=', 'registered_months.id')
                ->leftJoin('users', 'bills.paid_by', '=', 'users.id')
                ->where('bills.status', 'paid')
                ->where('bills.pam_id', $pamId)
                ->when($selectedPeriod, function ($query, $period) {
                    $year = substr($period, 0, 4);
                    $month = substr($period, 5, 2);
                    return $query->whereMonth('bills.paid_at', $month)
                        ->whereYear('bills.paid_at', $year);
                })
                ->when($selectedUser, function ($query, $userId) {
                    return $query->where('bills.paid_by', $userId);
                })
                ->orderBy('bills.paid_at', 'desc');

            $paymentData = $paymentDataQuery->get();

            if ($paymentData->isEmpty()) {
                return back()->with('error', 'Tidak ada data pembayaran untuk periode ' . $selectedPeriod);
            }

            // Calculate summary
            $totalPayments = $paymentData->count();
            $totalAmounts = $paymentData->sum(function ($item) {
                return is_numeric($item->total_bill) ? (float) $item->total_bill : 0;
            });

            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);

            // Create HTML content
            $html = $this->generatePaymentReportHtml($paymentData, $selectedPeriod, $totalPayments, $totalAmounts, $pam->name);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('PAM not found for download payment report: ' . $th->getMessage());
            return back()->with('error', 'PAM tidak ditemukan');
        } catch (\Throwable $th) {
            Log::error('Failed to generate payment report: ' . $th->getMessage());
            return back()->with('error', 'Gagal membuat laporan pembayaran');
        }
    }

    /**
     * Generate HTML content for PDF payment report
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
