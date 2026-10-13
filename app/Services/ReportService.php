<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleName;
use App\Models\Download;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations for the admin dashboard, publisher dashboard and reports.
 */
class ReportService
{
    public const TYPES = [
        'sales' => 'Sales report',
        'orders' => 'Orders report',
        'products' => 'Product report',
        'downloads' => 'Download report',
        'publishers' => 'Publisher report',
        'payments' => 'Payment report',
        'revenue' => 'Revenue report',
    ];

    // ------------------------------------------------------------------
    // Dashboards
    // ------------------------------------------------------------------

    /**
     * @return array<string, int|float>
     */
    public function adminStats(): array
    {
        $roleCounts = User::query()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.slug as slug, count(*) as total')
            ->groupBy('roles.slug')
            ->pluck('total', 'slug');

        $products = Product::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when approval_status = ? then 1 else 0 end) as pending', [ApprovalStatus::Pending->value])
            ->selectRaw('sum(case when is_free = ? then 1 else 0 end) as free', [true])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as published', [ProductStatus::Published->value])
            ->first();

        $payments = Payment::query()
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as pending', [PaymentRecordStatus::UnderReview->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as approved', [PaymentRecordStatus::Approved->value])
            ->first();

        return [
            'users' => (int) $roleCounts->sum(),
            'customers' => (int) ($roleCounts[RoleName::Customer->value] ?? 0),
            'publishers' => (int) ($roleCounts[RoleName::Publisher->value] ?? 0),
            'products' => (int) $products->total,
            'published_products' => (int) $products->published,
            'pending_products' => (int) $products->pending,
            'free_products' => (int) $products->free,
            'paid_products' => (int) $products->total - (int) $products->free,
            'orders' => Order::query()->count(),
            'pending_payments' => (int) $payments->pending,
            'approved_payments' => (int) $payments->approved,
            'revenue' => (float) Order::query()->where('payment_status', PaymentStatus::Approved->value)->sum('total'),
            'downloads' => Download::query()->count(),
        ];
    }

    /**
     * @return array<string, mixed> Chart.js-ready series for the admin dashboard.
     */
    public function adminCharts(DateRange $range): array
    {
        $days = $range->days();
        $paidOrders = Order::query()->where('payment_status', PaymentStatus::Approved->value);

        return [
            'labels' => array_map(fn ($d) => date('M j', strtotime($d)), $days),
            'orders' => $this->dailySeries(Order::query(), 'created_at', $range),
            'revenue' => $this->dailySeries($paidOrders, 'paid_at', $range, 'sum(total)'),
            'products' => $this->dailySeries(Product::query(), 'created_at', $range),
            'downloads' => $this->dailySeries(Download::query(), 'created_at', $range),
            'publishers' => $this->publisherActivity($range),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function publisherStats(User $publisher): array
    {
        $products = Product::query()
            ->where('user_id', $publisher->getKey())
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as published', [ProductStatus::Published->value])
            ->selectRaw('sum(case when approval_status = ? then 1 else 0 end) as pending', [ApprovalStatus::Pending->value])
            ->selectRaw('sum(case when approval_status = ? then 1 else 0 end) as rejected', [ApprovalStatus::Rejected->value])
            ->selectRaw('sum(case when approval_status = ? then 1 else 0 end) as drafts', [ApprovalStatus::Draft->value])
            ->selectRaw('coalesce(sum(download_count), 0) as downloads')
            ->first();

        $sales = $this->paidItems()
            ->where('order_items.publisher_id', $publisher->getKey())
            ->selectRaw('coalesce(sum(order_items.quantity), 0) as items, coalesce(sum(order_items.total), 0) as revenue')
            ->first();

        return [
            'products' => (int) $products->total,
            'published' => (int) $products->published,
            'pending' => (int) $products->pending,
            'rejected' => (int) $products->rejected,
            'drafts' => (int) $products->drafts,
            'downloads' => (int) $products->downloads,
            'sales' => (int) $sales->items,
            'revenue' => (float) $sales->revenue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publisherCharts(User $publisher, DateRange $range): array
    {
        $productIds = Product::withTrashed()->where('user_id', $publisher->getKey())->select('id');

        return [
            'labels' => array_map(fn ($d) => date('M j', strtotime($d)), $range->days()),
            'sales' => $this->dailySeries(
                $this->paidItems()->where('order_items.publisher_id', $publisher->getKey()),
                'orders.paid_at',
                $range,
                'sum(order_items.total)'
            ),
            'downloads' => $this->dailySeries(Download::query()->whereIn('product_id', $productIds), 'created_at', $range),
        ];
    }

    // ------------------------------------------------------------------
    // Reports
    // ------------------------------------------------------------------

    /**
     * @return array{title: string, summary: array<string, string|int|float>, columns: array<string, string>, rows: array<int, array<string, mixed>>, chart: array<string, mixed>|null}
     */
    public function build(string $type, DateRange $range): array
    {
        return match ($type) {
            'orders' => $this->ordersReport($range),
            'products' => $this->productsReport($range),
            'downloads' => $this->downloadsReport($range),
            'publishers' => $this->publishersReport($range),
            'payments' => $this->paymentsReport($range),
            'revenue' => $this->revenueReport($range),
            default => $this->salesReport($range),
        };
    }

    protected function salesReport(DateRange $range): array
    {
        $rows = $this->paidItems()
            ->whereBetween('orders.paid_at', $range->bounds())
            ->selectRaw('order_items.product_id, order_items.product_name, count(distinct order_items.order_id) as orders, sum(order_items.quantity) as quantity, sum(order_items.discount) as discount, sum(order_items.total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->get();

        $orders = Order::query()->where('payment_status', PaymentStatus::Approved->value)->whereBetween('paid_at', $range->bounds());
        $orderCount = (clone $orders)->count();
        $revenue = (float) (clone $orders)->sum('total');

        return [
            'title' => self::TYPES['sales'],
            'summary' => [
                'Paid orders' => $orderCount,
                'Items sold' => (int) $rows->sum('quantity'),
                'Revenue' => money($revenue),
                'Average order' => money($orderCount ? $revenue / $orderCount : 0),
            ],
            'columns' => ['product_name' => 'Product', 'orders' => 'Orders', 'quantity' => 'Qty', 'discount' => 'Discounts', 'revenue' => 'Revenue'],
            'rows' => $rows->map(fn ($r) => [
                'product_name' => $r->product_name,
                'orders' => (int) $r->orders,
                'quantity' => (int) $r->quantity,
                'discount' => money($r->discount),
                'revenue' => money($r->revenue),
            ])->all(),
            'chart' => $this->chart($range, 'Revenue', $this->dailySeries(clone $orders, 'paid_at', $range, 'sum(total)')),
        ];
    }

    protected function ordersReport(DateRange $range): array
    {
        $query = Order::query()->whereBetween('created_at', $range->bounds());
        $byStatus = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $summary = ['Total orders' => (int) $byStatus->sum(), 'Order value' => money((clone $query)->sum('total'))];
        foreach ($byStatus as $status => $count) {
            $summary[OrderStatus::from($status)->label()] = (int) $count;
        }

        return [
            'title' => self::TYPES['orders'],
            'summary' => $summary,
            'columns' => ['order_number' => 'Order', 'customer' => 'Customer', 'total' => 'Total', 'payment' => 'Payment', 'status' => 'Status', 'date' => 'Date'],
            'rows' => (clone $query)->latest('id')->limit(1000)->get()->map(fn (Order $o) => [
                'order_number' => $o->order_number,
                'customer' => $o->customer_name.' <'.$o->customer_email.'>',
                'total' => money($o->total, $o->currency),
                'payment' => $o->payment_status->label(),
                'status' => $o->status->label(),
                'date' => $o->created_at->format('Y-m-d H:i'),
            ])->all(),
            'chart' => $this->chart($range, 'Orders', $this->dailySeries(clone $query, 'created_at', $range)),
        ];
    }

    protected function productsReport(DateRange $range): array
    {
        $query = Product::withTrashed()->whereBetween('created_at', $range->bounds());
        $all = (clone $query)->with(['user:id,name', 'category:id,name'])->latest('id')->limit(1000)->get();

        return [
            'title' => self::TYPES['products'],
            'summary' => [
                'Products added' => $all->count(),
                'Published' => $all->where('status', ProductStatus::Published)->count(),
                'Pending review' => $all->where('approval_status', ApprovalStatus::Pending)->count(),
                'Rejected' => $all->where('approval_status', ApprovalStatus::Rejected)->count(),
                'Free' => $all->where('is_free', true)->count(),
                'Paid' => $all->where('is_free', false)->count(),
            ],
            'columns' => ['id' => 'ID', 'name' => 'Product', 'publisher' => 'Publisher', 'category' => 'Category', 'price' => 'Price', 'status' => 'Status', 'downloads' => 'Downloads', 'sales' => 'Sales', 'date' => 'Added'],
            'rows' => $all->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name.($p->trashed() ? ' (deleted)' : ''),
                'publisher' => $p->user?->name,
                'category' => $p->category?->name,
                'price' => $p->is_free ? 'Free' : money($p->finalPrice()),
                'status' => $p->status->label(),
                'downloads' => $p->download_count,
                'sales' => $p->sales_count,
                'date' => $p->created_at->format('Y-m-d'),
            ])->all(),
            'chart' => $this->chart($range, 'Products added', $this->dailySeries(clone $query, 'created_at', $range)),
        ];
    }

    protected function downloadsReport(DateRange $range): array
    {
        $query = Download::query()->whereBetween('downloads.created_at', $range->bounds());

        $rows = (clone $query)
            ->join('products', 'products.id', '=', 'downloads.product_id')
            ->selectRaw('downloads.product_id, products.name, products.is_free, count(*) as total, count(distinct downloads.user_id) as users')
            ->groupBy('downloads.product_id', 'products.name', 'products.is_free')
            ->orderByDesc('total')
            ->get();

        return [
            'title' => self::TYPES['downloads'],
            'summary' => [
                'Total downloads' => (clone $query)->count(),
                'Free downloads' => (clone $query)->where('is_free', true)->count(),
                'Paid downloads' => (clone $query)->where('is_free', false)->count(),
                'Unique users' => (clone $query)->whereNotNull('user_id')->distinct()->count('user_id'),
            ],
            'columns' => ['name' => 'Product', 'type' => 'Type', 'total' => 'Downloads', 'users' => 'Signed-in users'],
            'rows' => $rows->map(fn ($r) => [
                'name' => $r->name,
                'type' => $r->is_free ? 'Free' : 'Paid',
                'total' => (int) $r->total,
                'users' => (int) $r->users,
            ])->all(),
            'chart' => $this->chart($range, 'Downloads', $this->dailySeries(Download::query(), 'created_at', $range)),
        ];
    }

    protected function publishersReport(DateRange $range): array
    {
        $publishers = User::query()
            ->whereHas('role', fn (Builder $q) => $q->where('slug', RoleName::Publisher->value))
            ->with('publisherProfile:id,user_id,display_name,status')
            ->withCount([
                'products',
                'products as published_count' => fn (Builder $q) => $q->where('status', ProductStatus::Published->value),
                'products as added_in_range' => fn (Builder $q) => $q->whereBetween('created_at', $range->bounds()),
            ])
            ->get();

        $sales = $this->paidItems()
            ->whereBetween('orders.paid_at', $range->bounds())
            ->selectRaw('order_items.publisher_id, sum(order_items.quantity) as items, sum(order_items.total) as revenue')
            ->groupBy('order_items.publisher_id')
            ->get()
            ->keyBy('publisher_id');

        $downloads = Download::query()
            ->join('products', 'products.id', '=', 'downloads.product_id')
            ->whereBetween('downloads.created_at', $range->bounds())
            ->selectRaw('products.user_id, count(*) as total')
            ->groupBy('products.user_id')
            ->pluck('total', 'products.user_id');

        $rows = $publishers->map(fn (User $u) => [
            'name' => $u->publicName(),
            'status' => $u->publisherProfile?->status?->label() ?? '-',
            'products' => $u->products_count,
            'published' => $u->published_count,
            'added' => $u->added_in_range,
            'sales' => (int) ($sales[$u->id]->items ?? 0),
            'revenue_raw' => (float) ($sales[$u->id]->revenue ?? 0),
            'downloads' => (int) ($downloads[$u->id] ?? 0),
        ])->sortByDesc('revenue_raw')->values();

        return [
            'title' => self::TYPES['publishers'],
            'summary' => [
                'Publishers' => $publishers->count(),
                'Products added' => (int) $rows->sum('added'),
                'Items sold' => (int) $rows->sum('sales'),
                'Revenue' => money($rows->sum('revenue_raw')),
            ],
            'columns' => ['name' => 'Publisher', 'status' => 'Status', 'products' => 'Products', 'published' => 'Published', 'added' => 'Added (range)', 'sales' => 'Sold (range)', 'revenue' => 'Revenue (range)', 'downloads' => 'Downloads (range)'],
            'rows' => $rows->map(function (array $row) {
                $row['revenue'] = money($row['revenue_raw']);
                unset($row['revenue_raw']);

                return $row;
            })->all(),
            'chart' => null,
        ];
    }

    protected function paymentsReport(DateRange $range): array
    {
        $query = Payment::query()->whereBetween('created_at', $range->bounds());
        $byStatus = (clone $query)->selectRaw('status, count(*) as total, sum(amount) as amount')->groupBy('status')->get()->keyBy('status');

        $summary = ['Submissions' => (int) $byStatus->sum('total')];
        foreach (PaymentRecordStatus::cases() as $status) {
            $summary[$status->label()] = (int) ($byStatus[$status->value]->total ?? 0);
        }
        $summary['Approved amount'] = money($byStatus[PaymentRecordStatus::Approved->value]->amount ?? 0);

        return [
            'title' => self::TYPES['payments'],
            'summary' => $summary,
            'columns' => ['id' => 'Payment', 'order' => 'Order', 'customer' => 'Customer', 'gateway' => 'Gateway', 'amount' => 'Amount', 'transaction' => 'Transaction ID', 'status' => 'Status', 'date' => 'Date'],
            'rows' => (clone $query)->with(['order:id,order_number,customer_name', 'gateway:id,name'])->latest('id')->limit(1000)->get()->map(fn (Payment $p) => [
                'id' => '#'.$p->id,
                'order' => $p->order?->order_number,
                'customer' => $p->order?->customer_name,
                'gateway' => $p->gateway?->name,
                'amount' => money($p->amount, $p->currency),
                'transaction' => $p->transaction_id,
                'status' => $p->status->label(),
                'date' => $p->created_at->format('Y-m-d H:i'),
            ])->all(),
            'chart' => $this->chart($range, 'Payments', $this->dailySeries(clone $query, 'created_at', $range)),
        ];
    }

    protected function revenueReport(DateRange $range): array
    {
        $orders = Order::query()->where('payment_status', PaymentStatus::Approved->value)->whereBetween('paid_at', $range->bounds());
        $series = $this->dailySeries(clone $orders, 'paid_at', $range, 'sum(total)');
        $counts = $this->dailySeries(clone $orders, 'paid_at', $range);

        $byGateway = (clone $orders)
            ->leftJoin('payment_gateways', 'payment_gateways.id', '=', 'orders.payment_gateway_id')
            ->selectRaw("coalesce(payment_gateways.name, 'Manual') as gateway, sum(orders.total) as revenue")
            ->groupBy('gateway')
            ->pluck('revenue', 'gateway');

        $summary = ['Total revenue' => money(array_sum($series)), 'Paid orders' => array_sum($counts)];
        foreach ($byGateway as $gateway => $revenue) {
            $summary[$gateway] = money($revenue);
        }

        $rows = [];
        foreach ($range->days() as $i => $day) {
            if ($counts[$i] > 0) {
                $rows[] = ['date' => $day, 'orders' => $counts[$i], 'revenue' => money($series[$i])];
            }
        }

        return [
            'title' => self::TYPES['revenue'],
            'summary' => $summary,
            'columns' => ['date' => 'Date', 'orders' => 'Paid orders', 'revenue' => 'Revenue'],
            'rows' => array_reverse($rows),
            'chart' => $this->chart($range, 'Revenue', $series),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Order items of paid orders.
     */
    protected function paidItems(): QueryBuilder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->where('orders.payment_status', PaymentStatus::Approved->value);
    }

    /**
     * Values per day for the range, zero-filled.
     *
     * @return array<int, float|int>
     */
    public function dailySeries(Builder|QueryBuilder $query, string $column, DateRange $range, string $aggregate = 'count(*)'): array
    {
        $values = (clone $query)
            ->whereBetween($column, $range->bounds())
            ->selectRaw("DATE({$column}) as day, {$aggregate} as value")
            ->groupBy(DB::raw("DATE({$column})"))
            ->pluck('value', 'day');

        return array_map(
            fn (string $day) => round((float) ($values[$day] ?? 0), 2),
            $range->days()
        );
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, string>, data: array<int, string>}
     */
    protected function publisherActivity(DateRange $range): array
    {
        $rows = Product::withTrashed()
            ->join('users', 'users.id', '=', 'products.user_id')
            ->leftJoin('publisher_profiles', 'publisher_profiles.user_id', '=', 'users.id')
            ->whereBetween('products.created_at', $range->bounds())
            ->selectRaw('products.user_id, coalesce(publisher_profiles.display_name, users.name) as name, count(*) as total')
            ->groupBy('products.user_id', 'publisher_profiles.display_name', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * @param  array<int, float|int>  $data
     * @return array<string, mixed>
     */
    protected function chart(DateRange $range, string $label, array $data): array
    {
        return [
            'labels' => array_map(fn ($d) => date('M j', strtotime($d)), $range->days()),
            'label' => $label,
            'data' => $data,
        ];
    }
}
