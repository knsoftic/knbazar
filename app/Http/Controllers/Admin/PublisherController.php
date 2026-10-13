<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PublisherStatusChanged;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublisherController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $publishers = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', RoleName::Publisher->value))
            ->with('publisherProfile')
            ->when(in_array($status, PublisherStatus::values(), true), fn ($q) => $q->whereHas('publisherProfile', fn ($p) => $p->where('status', $status)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('username', 'like', $like)
                    ->orWhereHas('publisherProfile', fn ($p) => $p->where('display_name', 'like', $like)));
            })
            ->withCount(['products', 'products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.publishers.index', ['publishers' => $publishers, 'status' => $status]);
    }

    public function show(User $user, ReportService $reports): View
    {
        abort_unless($user->isPublisher(), 404);

        $user->load('publisherProfile.approver:id,name');

        return view('admin.publishers.show', [
            'publisher' => $user,
            'stats' => $reports->publisherStats($user),
            'products' => $user->products()->with('category:id,name')->latest('id')->limit(20)->get(),
        ]);
    }

    public function updateStatus(Request $request, User $user, ActivityLogger $logger, NotificationService $notifications): RedirectResponse
    {
        abort_unless($user->isPublisher() && $user->publisherProfile, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(PublisherStatus::class)],
            'reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ], ['reason.required_if' => 'Please give a reason when rejecting a publisher.']);

        $profile = $user->publisherProfile;
        $status = PublisherStatus::from($data['status']);

        $profile->forceFill([
            'status' => $status,
            'rejection_reason' => $status === PublisherStatus::Rejected ? $data['reason'] : null,
            'approved_at' => $status === PublisherStatus::Approved ? now() : $profile->approved_at,
            'approved_by' => $status === PublisherStatus::Approved ? $request->user()->getKey() : $profile->approved_by,
        ])->save();

        $notifications->send($user, new PublisherStatusChanged($profile));
        $logger->log('publisher.status_changed', $user, "Publisher {$user->username} set to {$status->label()}", array_filter(['reason' => $data['reason'] ?? null]));

        return back()->with('success', 'Publisher status updated to '.$status->label().'.');
    }
}
