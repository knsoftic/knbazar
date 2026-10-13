<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = ContactMessage::query()
            ->when($request->query('filter') === 'unread', fn ($q) => $q->where('is_read', false))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('subject', 'like', $like));
            })
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.messages.index', ['messages' => $messages]);
    }

    public function show(ContactMessage $message): View
    {
        $message->markAsRead();

        return view('admin.messages.show', ['message' => $message]);
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $message->is_read ? $message->markAsUnread() : $message->markAsRead();

        return back()->with('success', 'Message marked as '.($message->is_read ? 'read.' : 'unread.'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}
