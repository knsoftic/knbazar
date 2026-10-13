<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['gateway:id,name', 'user:id,name,role_id'])
            ->withCount('items')
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('order_number', 'like', $like)->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)->orWhere('transaction_id', 'like', $like));
            })
            ->when(in_array($request->query('status'), OrderStatus::values(), true), fn ($q) => $q->where('status', $request->query('status')))
            ->when(in_array($request->query('payment'), PaymentStatus::values(), true), fn ($q) => $q->where('payment_status', $request->query('payment')))
            ->when($request->filled('gateway'), fn ($q) => $q->where('payment_gateway_id', (int) $request->query('gateway')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'gateways' => PaymentGateway::withTrashed()->ordered()->get(['id', 'name']),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product:id,name,slug,thumbnail,image,deleted_at',
            'items.publisher:id,name,role_id',
            'payments.gateway:id,name',
            'payments.reviewer:id,name,role_id',
            'gateway',
            'statusHistories.user:id,name,role_id',
            'downloadPermissions.product:id,name,deleted_at',
        ]);

        return view('admin.orders.show', ['order' => $order]);
    }

    public function updateStatus(OrderStatusRequest $request, Order $order): RedirectResponse
    {
        $status = OrderStatus::from($request->validated('status'));
        $this->orders->updateStatus($order, $status, $request->user(), $request->validated('note'));

        return back()->with('success', 'Order status updated to '.$status->label().'.');
    }

    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->orders->addAdminNote($order, $data['note'], $request->user());

        return back()->with('success', 'Internal note added.');
    }
}
