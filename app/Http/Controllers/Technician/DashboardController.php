<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated technician's operational dashboard.
     */
    public function index(Request $request): View
    {
        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        $jobOrders = JobOrder::query()
            ->where('technician_id', $technician->id);

        $assignedCount = (clone $jobOrders)
            ->where('status', 'assigned')
            ->count();

        $inProgressCount = (clone $jobOrders)
            ->where('status', 'in_progress')
            ->count();

        $completedCount = (clone $jobOrders)
            ->where('status', 'completed')
            ->count();

        $activeJobOrder = (clone $jobOrders)
            ->with([
                'customer',
                'serviceRequest',
            ])
            ->where('status', 'in_progress')
            ->orderBy('started_at')
            ->first();

        $upcomingJobOrders = (clone $jobOrders)
            ->with([
                'customer',
                'serviceRequest',
            ])
            ->where('status', 'assigned')
            ->orderByRaw('scheduled_date IS NULL')
            ->orderBy('scheduled_date')
            ->orderByRaw('scheduled_time IS NULL')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        return view('technician.dashboard', [
            'technician' => $technician,
            'assignedCount' => $assignedCount,
            'inProgressCount' => $inProgressCount,
            'completedCount' => $completedCount,
            'activeJobOrder' => $activeJobOrder,
            'upcomingJobOrders' => $upcomingJobOrders,
        ]);
    }
}
