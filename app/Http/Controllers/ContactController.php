<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Services\SeoService;
use App\Services\WhatsAppOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(SeoService $seo, WhatsAppOrderService $whatsapp): View
    {
        $page = Page::query()->active()->where('slug', 'contact')->first();
        $meta = $page ? $seo->forPage($page) : $seo->defaults('Contact us');
        $meta->canonical = route('contact.show');

        return view('pages.contact', [
            'page' => $page,
            'whatsappUrl' => setting('whatsapp_enabled') ? $whatsapp->chatUrl('Hello, I have a question.') : null,
            'seo' => $meta,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::query()->create($request->safe()->only(['name', 'email', 'phone', 'subject', 'message']) + [
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 495, ''),
        ]);

        Log::channel('marketplace')->info('[contact] New contact message', ['id' => $message->getKey()]);

        return redirect()->route('contact.show')->with('success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
