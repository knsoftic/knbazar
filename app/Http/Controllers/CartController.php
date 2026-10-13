<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cart, SeoService $seo): View
    {
        return view('cart.index', [
            'lines' => $cart->lines(),
            'totals' => $cart->totals(),
            'allowQuantity' => (bool) setting('cart_allow_quantity'),
            'maxQuantity' => $cart->maxQuantity(),
            'seo' => $seo->defaults('Your cart')->noindex(),
        ]);
    }

    public function add(Request $request, Product $product, CartService $cart): RedirectResponse|JsonResponse
    {
        $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:100']]);

        try {
            $cart->add($product, (int) $request->input('quantity', 1), $request->user());
        } catch (ValidationException $e) {
            return $this->respondError($request, $e->validator->errors()->first());
        }

        $message = '"'.$product->name.'" was added to your cart.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'count' => $cart->count(), 'cart_url' => route('cart.index')]);
        }

        return back()->with('success', $message);
    }

    public function buyNow(Request $request, Product $product, CartService $cart): RedirectResponse
    {
        try {
            if (! $cart->has($product->getKey())) {
                $cart->add($product, 1, $request->user());
            }
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()->route('checkout.index');
    }

    public function update(Request $request, int $productId, CartService $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $cart->update($productId, (int) $data['quantity']);

        if ($request->expectsJson()) {
            return response()->json(['count' => $cart->count(), 'totals' => $cart->totals()]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function remove(Request $request, int $productId, CartService $cart): RedirectResponse|JsonResponse
    {
        $cart->remove($productId);

        if ($request->expectsJson()) {
            return response()->json(['count' => $cart->count()]);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }

    public function clear(CartService $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('cart.index')->with('success', 'Your cart is now empty.');
    }

    protected function respondError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
