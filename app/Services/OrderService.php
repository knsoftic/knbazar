<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DownloadPermission;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Order lifecycle:
 * Awaiting Payment -> Payment Submitted -> (admin verification) -> Completed (paid, downloads enabled)
 *                                        \-> payment rejected -> Awaiting Payment (resubmit)
 * Cancelled / Refunded close the order and revoke download access.
 */
class OrderService
{
    public function __construct(
        protected ActivityLogger $logger,
        protected NotificationService $notifications,
    ) {}

    /**
     * @param  array{customer_name: string, customer_email: string, customer_phone?: string|null, customer_country?: string|null, customer_address?: string|null, notes?: string|null}  $customer
     */
    public function createFromCart(User $user, CartService $cart, PaymentGateway $gateway, array $customer): Order
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty or its products are no longer available.']);
        }

        if (! $gateway->is_active) {
            throw ValidationException::withMessages(['payment_gateway_id' => 'The selected payment method is not available.']);
        }

        foreach ($lines as $line) {
            if ($cart->ownsProduct($user, $line['product'])) {
                throw ValidationException::withMessages(['cart' => 'You already own "'.$line['product']->name.'". Remove it from your cart to continue.']);
            }
        }

        $totals = $cart->totals();
        $request = request();

        $order = DB::transaction(function () use ($user, $lines, $gateway, $customer, $totals, $request) {
            $order = Order::query()->create([
                'order_number' => 'TMP-'.Str::uuid(),
                'user_id' => $user->getKey(),
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'] ?? null,
                'customer_country' => $customer['customer_country'] ?? null,
                'customer_address' => $customer['customer_address'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'total' => $totals['total'],
                'currency' => $gateway->currency ?: currency_code(),
                'payment_gateway_id' => $gateway->getKey(),
                'payment_status' => PaymentStatus::Pending,
                'status' => OrderStatus::AwaitingPayment,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 495, ''),
            ]);

            $order->forceFill(['order_number' => $this->orderNumberFor($order)])->save();

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'publisher_id' => $product->user_id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'unit_price' => $line['unit_price'],
                    'sale_price' => $line['price'],
                    'discount' => $line['discount'],
                    'quantity' => $line['quantity'],
                    'total' => $line['total'],
                ]);
            }

            $this->recordHistory($order, null, OrderStatus::AwaitingPayment, $user, 'Order placed');

            return $order;
        });

        $cart->clear();
        $this->logger->log('order.created', $order, "Order {$order->order_number} placed", ['total' => $order->total], $user);

        return $order;
    }

    public function orderNumberFor(Order $order): string
    {
        return sprintf('%s-%s-%06d', config('marketplace.order_number_prefix', 'ORD'), ($order->created_at ?? now())->format('Y'), $order->getKey());
    }

    /**
     * Admin status change with the side effects each status implies.
     */
    public function updateStatus(Order $order, OrderStatus $status, User $actor, ?string $note = null): Order
    {
        return match ($status) {
            OrderStatus::Completed => $this->markCompleted($order, $actor, $note),
            OrderStatus::Cancelled => $this->cancel($order, $actor, $note),
            OrderStatus::Refunded => $this->refund($order, $actor, $note),
            default => $this->transition($order, $status, $actor, $note),
        };
    }

    /**
     * Mark complete. If the payment was not verified yet, completing the order
     * counts as manual payment confirmation (e.g. a WhatsApp order paid offline).
     */
    public function markCompleted(Order $order, User $actor, ?string $note = null): Order
    {
        DB::transaction(function () use ($order, $actor, $note) {
            $wasPaid = $order->isPaid();

            $order->forceFill([
                'payment_status' => PaymentStatus::Approved,
                'paid_at' => $order->paid_at ?? now(),
                'completed_at' => now(),
            ]);

            $this->transition($order, OrderStatus::Completed, $actor, $note ?: ($wasPaid ? 'Order completed' : 'Completed and payment confirmed manually'));
            $this->grantDownloadPermissions($order);

            if (! $wasPaid) {
                $this->incrementSales($order);
            }
        });

        $this->notifications->send($order->user, new OrderCompleted($order));

        return $order;
    }

    public function cancel(Order $order, User $actor, ?string $note = null): Order
    {
        DB::transaction(function () use ($order, $actor, $note) {
            $order->forceFill(['cancelled_at' => now()]);
            $this->transition($order, OrderStatus::Cancelled, $actor, $note ?: 'Order cancelled');
            $this->revokeDownloadPermissions($order);
        });

        return $order;
    }

    public function refund(Order $order, User $actor, ?string $note = null): Order
    {
        DB::transaction(function () use ($order, $actor, $note) {
            $this->transition($order, OrderStatus::Refunded, $actor, $note ?: 'Order refunded');
            $this->revokeDownloadPermissions($order);
        });

        return $order;
    }

    public function addAdminNote(Order $order, string $note, User $actor): Order
    {
        $entry = '['.now()->format('Y-m-d H:i').' - '.$actor->name.'] '.trim($note);
        $order->forceFill(['admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n" : '').$entry)])->save();

        $this->logger->log('order.note_added', $order, "Internal note added to order {$order->order_number}", user: $actor);

        return $order;
    }

    public function grantDownloadPermissions(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $limit = (int) setting('download_limit_per_purchase', 0);
        $days = (int) setting('download_expiry_days', 0);

        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            $permission = DownloadPermission::query()->firstOrNew([
                'user_id' => $order->user_id,
                'product_id' => $item->product_id,
                'order_id' => $order->getKey(),
            ]);

            $permission->fill([
                'order_item_id' => $item->getKey(),
                'download_limit' => $permission->exists ? $permission->download_limit : ($limit > 0 ? $limit * max(1, $item->quantity) : null),
                'expires_at' => $permission->exists ? $permission->expires_at : ($days > 0 ? now()->addDays($days) : null),
                'revoked_at' => null,
            ])->save();
        }
    }

    public function revokeDownloadPermissions(Order $order): void
    {
        $order->downloadPermissions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function incrementSales(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            // Query builder increment so the product's updated_at is not touched.
            DB::table('products')->where('id', $item->product_id)->increment('sales_count', $item->quantity);
        }
    }

    public function transition(Order $order, OrderStatus $status, ?User $actor, ?string $note = null): Order
    {
        $from = $order->status;
        $order->forceFill(['status' => $status])->save();

        if ($from !== $status || $note) {
            $this->recordHistory($order, $from, $status, $actor, $note);
            $this->logger->log('order.status_changed', $order, "Order {$order->order_number}: ".($from?->label() ?? '-').' -> '.$status->label(), array_filter(['note' => $note]), $actor);
        }

        return $order;
    }

    public function recordHistory(Order $order, ?OrderStatus $from, OrderStatus $to, ?User $actor, ?string $note = null): void
    {
        $order->statusHistories()->create([
            'user_id' => $actor?->getKey(),
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
        ]);
    }
}
