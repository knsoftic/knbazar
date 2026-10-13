<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, SeoService $seo): View
    {
        $user = $request->user();

        return view($user->isAdmin() ? 'admin.notifications' : ($user->isPublisher() ? 'publisher.notifications' : 'account.notifications'), [
            'notifications' => $user->notifications()->paginate(20),
            'seo' => $seo->defaults('Notifications')->noindex(),
        ]);
    }

    /**
     * Mark a notification as read and follow its link (internal URLs only).
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');
        $isInternal = Str::startsWith($url, url('/'));

        return redirect()->to($isInternal ? $url : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
