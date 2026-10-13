<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Page;
use App\Models\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\SeoService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function index(Request $request, CartService $cart, SeoService $seo): View|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty.');
        }

        $user = $request->user();
        $owned = $lines->filter(fn ($line) => $cart->ownsProduct($user, $line['product']));

        $termsSlug = setting('checkout_terms_page');

        return view('checkout.index', [
            'lines' => $lines,
            'totals' => $cart->totals(),
            'owned' => $owned,
            'gateways' => PaymentGateway::query()->active()->ordered()->get(),
            'countries' => Countries::all(),
            'termsPage' => $termsSlug ? Page::query()->active()->where('slug', $termsSlug)->first(['id', 'title', 'slug']) : null,
            'user' => $user,
            'seo' => $seo->defaults('Checkout')->noindex(),
        ]);
    }

    public function store(CheckoutRequest $request, CartService $cart, OrderService $orders, PaymentGatewayManager $gateways): Response
    {
        $gateway = PaymentGateway::query()->active()->findOrFail($request->validated('payment_gateway_id'));

        $order = $orders->createFromCart($request->user(), $cart, $gateway, $request->safe()->only([
            'customer_name', 'customer_email', 'customer_phone', 'customer_country', 'customer_address', 'notes',
        ]));

        return $gateways->driver($gateway)->initiate($order, $gateway);
    }
}
