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
     * Cancel payment - change bill status to pending and meter reading to pending
     */
    public function cancelPayment(Request $request, $pamId, $billId)
    {
        try {
            // Find bill with relationships
            $bill = Bill::with(['meterReading', 'customer'])->where('id', $billId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate that bill is currently paid
            if ($bill->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya tagihan dengan status Lunas yang dapat dibatalkan'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update bill status to pending
                $bill->update([
                    'status' => 'pending',
                    'paid_at' => null,
                    'paid_by' => null,
                    'payment_method' => null
                ]);

                // Update meter reading status to pending
                if ($bill->meterReading) {
                    $bill->meterReading->update(['status' => 'pending']);
                }

                Log::info("Payment cancelled for bill {$billId} - status changed to pending");

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran berhasil dibatalkan, status tagihan dan pembacaan meter diubah menjadi Menunggu',
                    'data' => [
                        'bill_id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'customer_name' => $bill->customer->name ?? '-'
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Bill not found for cancel payment: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan'
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to cancel payment: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pembayaran'
            ], 500);
        }
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
     * Mark bill as paid - change meter reading and bill status to paid
     */
    public function markAsPaid(Request $request, $pamId, $billId)
    {
        try {
            // Find bill with relationships
            $bill = Bill::with(['meterReading', 'customer'])->where('id', $billId)
                ->where('pam_id', $pamId)
                ->firstOrFail();

            // Validate that bill is currently pending
            if ($bill->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya tagihan dengan status Menunggu yang dapat ditandai lunas'
                ], 422);
            }

            // Validate request data
            $validated = $request->validate([
                'payment_method' => 'required|string|max:50'
            ], [
                'payment_method.required' => 'Metode pembayaran wajib diisi',
                'payment_method.max' => 'Metode pembayaran maksimal 50 karakter'
            ]);

            DB::beginTransaction();

            try {
                // Update bill status to paid
                $bill->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::check() ? Auth::id() : null,
                    'payment_method' => $validated['payment_method']
                ]);

                // Update meter reading status to paid
                if ($bill->meterReading) {
                    $bill->meterReading->update(['status' => 'paid']);
                }

                Log::info("Bill {$billId} marked as paid", [
                    'bill_number' => $bill->bill_number,
                    'payment_method' => $validated['payment_method'],
                    'paid_by' => Auth::check() ? Auth::id() : null
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan berhasil ditandai lunas',
                    'data' => [
                        'bill_id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'customer_name' => $bill->customer->name ?? '-',
                        'paid_at' => $bill->paid_at->format('d M Y H:i'),
                        'payment_method' => $validated['payment_method']
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $th) {
            Log::error('Bill not found for mark as paid: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $th) {
            Log::error('Validation failed for mark as paid: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $th->validator->errors()->all())
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Failed to mark bill as paid: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai tagihan lunas'
            ], 500);
        }
    }
}
