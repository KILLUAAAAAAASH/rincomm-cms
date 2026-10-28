<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfileUpdateRequest;
use App\Services\CustomerProfileUpdateReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileUpdateReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('status', 'pending');
        if (! in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $filter = 'pending';
        }

        $items = CustomerProfileUpdateRequest::query()
            ->with(['customer.user', 'requester', 'reviewer'])
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.subscribers.profile-update-requests.index', compact('items', 'filter'));
    }

    public function approve(Request $request, CustomerProfileUpdateRequest $profileUpdateRequest, CustomerProfileUpdateReviewService $service): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->decide($profileUpdateRequest, $request->user(), 'approved', $validated['review_notes'], $request);

        return redirect()->route('admin.profile-update-requests.index')
            ->with('success', 'Profile update approved and applied.');
    }

    public function reject(Request $request, CustomerProfileUpdateRequest $profileUpdateRequest, CustomerProfileUpdateReviewService $service): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->decide($profileUpdateRequest, $request->user(), 'rejected', $validated['review_notes'], $request);

        return redirect()->route('admin.profile-update-requests.index')
            ->with('success', 'Profile update request rejected. Customer information was not changed.');
    }
}
