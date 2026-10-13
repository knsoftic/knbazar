<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): View
    {
        $range = DateRange::fromRequest($request, 'last_30_days');
        $user = $request->user();

        return view('admin.dashboard', [
            'stats' => $reports->adminStats(),
            'charts' => $reports->adminCharts($range),
            'range' => $range,
            'pendingProducts' => $user->can('view_products')
                ? Product::query()->where('approval_status', ApprovalStatus::Pending->value)->with(['user:id,name,role_id', 'category:id,name'])->oldest('submitted_at')->limit(6)->get()
                : collect(),
            'pendingPayments' => $user->can('manage_payments')
                ? Payment::query()->where('status', PaymentRecordStatus::UnderReview->value)->with(['order:id,order_number,customer_name', 'gateway:id,name'])->oldest('id')->limit(6)->get()
                : collect(),
            'recentOrders' => $user->can('manage_orders')
                ? Order::query()->latest('id')->limit(6)->get()
                : collect(),
        ]);
    }
}
