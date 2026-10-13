<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Orders that contain the publisher's products. Only the information a
 * publisher needs is exposed (no customer contact or payment details).
 */
class OrderController extends Controller
{
    public function index(Request $request, SeoService $seo): View
    {
        $items = OrderItem::query()
            ->where('publisher_id', $request->user()->getKey())
            ->with(['order:id,order_number,customer_name,status,payment_status,created_at,currency', 'product:id,name,slug'])
            ->latest('id')
            ->paginate(config('marketplace.pagination.account'));

        return view('publisher.orders', [
            'items' => $items,
            'seo' => $seo->defaults('Orders for my products')->noindex(),
        ]);
    }
}
