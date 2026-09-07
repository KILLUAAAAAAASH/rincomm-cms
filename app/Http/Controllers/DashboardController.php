<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceApplication;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalCustomers = Customer::count();

        $activeSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->count();

        $openTickets = ServiceRequest::query()
            ->where('status', 'open')
            ->count();

        $pendingJobOrders = JobOrder::query()
            ->where('status', 'pending')
            ->count();

        $pendingApplicationsCount = ServiceApplication::query()
            ->where('status', 'pending')
            ->count();

        $pendingApplications = ServiceApplication::query()
            ->select([
                'id',
                'application_number',
                'service_plan_id',
                'first_name',
                'middle_name',
                'last_name',
                'status',
                'submitted_at',
                'created_at',
            ])
            ->with([
                'servicePlan:id,name',
            ])
            ->where('status', 'pending')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalCustomers' => $totalCustomers,
            'activeSubscriptions' => $activeSubscriptions,
            'openTickets' => $openTickets,
            'pendingJobOrders' => $pendingJobOrders,
            'pendingApplicationsCount' => $pendingApplicationsCount,
            'pendingApplications' => $pendingApplications,
        ]);
    }
}
