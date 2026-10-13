<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, SeoService $seo): View
    {
        $orders = $request->user()->orders()
            ->with(['items:id,order_id,product_name', 'gateway:id,name'])
            ->latest('id')
            ->paginate(config('marketplace.pagination.account'));

        return view('account.orders.index', [
            'orders' => $orders,
            'seo' => $seo->defaults('My orders')->noindex(),
        ]);
    }

    public function show(Order $order, SeoService $seo): View
    {
        $this->authorize('view', $order);

        $order->load([
            'items.product:id,name,slug,image,thumbnail,is_free,file_path,deleted_at',
            'payments.gateway:id,name',
            'gateway',
            'statusHistories',
            'downloadPermissions',
        ]);

        return view('account.orders.show', [
            'order' => $order,
            'seo' => $seo->defaults('Order '.$order->order_number)->noindex(),
        ]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $orders->cancel($order, $request->user(), 'Cancelled by customer');

        return redirect()->route('orders.show', $order)->with('success', 'Order '.$order->order_number.' has been cancelled.');
    }
}
