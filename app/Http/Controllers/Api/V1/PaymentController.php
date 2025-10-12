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
}
