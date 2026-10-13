<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request, SeoService $seo): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin() && ! $request->boolean('customer')) {
            return redirect()->route('admin.dashboard');
        }

        return view('account.index', [
            'user' => $user,
            'stats' => [
                'orders' => $user->orders()->count(),
                'awaiting' => $user->orders()->whereIn('status', [OrderStatus::AwaitingPayment->value, OrderStatus::PaymentSubmitted->value])->count(),
                'purchased' => $user->downloadPermissions()->valid()->distinct()->count('product_id'),
                'downloads' => $user->downloads()->count(),
            ],
            'recentOrders' => $user->orders()->withCount('items')->latest('id')->limit(5)->get(),
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'seo' => $seo->defaults('My account')->noindex(),
        ]);
    }

    public function purchases(Request $request, SeoService $seo): View
    {
        $permissions = $request->user()->downloadPermissions()
            ->with(['product' => fn ($q) => $q->with(['category:id,name,slug', 'user:id,name,username,role_id', 'user.publisherProfile:id,user_id,display_name']), 'order:id,order_number,status,payment_status'])
            ->latest('id')
            ->get()
            ->unique('product_id');

        return view('account.purchases', [
            'permissions' => $permissions,
            'seo' => $seo->defaults('Purchased products')->noindex(),
        ]);
    }

    public function payments(Request $request, SeoService $seo): View
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->getKey())
            ->with(['order:id,order_number', 'gateway:id,name'])
            ->latest('id')
            ->paginate(config('marketplace.pagination.account'));

        return view('account.payments', [
            'payments' => $payments,
            'seo' => $seo->defaults('Payment history')->noindex(),
        ]);
    }
}
