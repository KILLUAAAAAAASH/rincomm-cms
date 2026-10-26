<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerConnectionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConnectionStatusController extends Controller
{
    public function show(Request $request, CustomerConnectionStatusService $statusService): JsonResponse
    {
        $customer = $request->user()->customer;

        $result = $customer === null
            ? ['status' => 'unavailable', 'checked_at' => null]
            : $statusService->forCustomer($customer);

        return response()->json($result)
            ->header('Cache-Control', 'private, no-store');
    }
}
