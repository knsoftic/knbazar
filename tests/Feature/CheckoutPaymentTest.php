<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCompleted;
use App\Notifications\PaymentApproved;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_add_update_remove(): void
    {
        $product = $this->publishedProduct(['price' => 2500, 'sale_price' => 1999]);

        $this->post(route('cart.add', $product))->assertSessionHas('success');
        $this->get('/cart')->assertOk()->assertSee($product->name)->assertSee('1,999');

        // AJAX add returns JSON with the cart count.
        $this->postJson(route('cart.add', $product))->assertOk()->assertJson(['count' => 1]);

        $this->delete(route('cart.remove', $product->id))->assertRedirect(route('cart.index'));
        $this->get('/cart')->assertSee('Your cart is empty');
    }

    public function test_free_and_unpublished_products_cannot_be_added_to_cart(): void
    {
        $free = $this->publishedProduct([], free: true);
        $this->post(route('cart.add', $free))->assertSessionHas('error');

        $draft = Product::factory()->create();
        $this->post(route('cart.add', $draft))->assertSessionHas('error');

        $this->get('/cart')->assertSee('Your cart is empty');
    }

    public function test_checkout_requires_login(): void
    {
        $product = $this->publishedProduct();
        $this->post(route('cart.add', $product));

        $this->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_full_paid_order_flow_with_payment_approval_and_download(): void
    {
        Notification::fake();
        $product = $this->publishedProduct(['price' => 2500, 'sale_price' => 1999]);
        $gateway = $this->gateway(requiresProof: true);
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin(['manage_payments'])->create();

        // Buy now -> checkout
        $this->actingAs($customer)->post(route('cart.buy-now', $product))->assertRedirect(route('checkout.index'));
        $this->get('/checkout')->assertOk()->assertSee($gateway->name);

        $this->post('/checkout', [
            'customer_name' => 'Usman Ali',
            'customer_email' => 'usman@example.com',
            'customer_phone' => '+92 300 1234567',
            'customer_country' => 'Pakistan',
            'payment_gateway_id' => $gateway->id,
            'terms' => '1',
        ])->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $order->order_number);
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(1999.0, (float) $order->total);
        $this->assertSame(501.0, (float) $order->discount_total);
        $this->assertSame($product->id, $order->items->first()->product_id);
        $this->get('/cart')->assertSee('Your cart is empty');

        // Not downloadable before payment approval
        $this->get(route('downloads.download', $product->slug))->assertRedirect(route('products.show', $product->slug));

        // Screenshot required for this gateway
        $this->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'TXN123456', 'amount' => '1999',
        ])->assertSessionHasErrors('proof');

        Storage::fake('local');
        $this->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id,
            'transaction_id' => 'TXN123456',
            'sender_name' => 'Usman Ali',
            'amount' => '1999',
            'proof' => UploadedFile::fake()->image('proof.jpg', 600, 900),
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $payment = Payment::query()->firstOrFail();
        $this->assertSame(OrderStatus::PaymentSubmitted, $order->status);
        $this->assertSame(PaymentStatus::Submitted, $order->payment_status);
        $this->assertSame(PaymentRecordStatus::UnderReview, $payment->status);
        Storage::disk('local')->assertExists($payment->proof_path);
        Notification::assertSentTo($admin, PaymentSubmitted::class);

        // Customer can view their own screenshot; others cannot.
        $this->get(route('orders.payments.proof', [$order, $payment]))->assertOk();
        $this->actingAs(User::factory()->customer()->create())->get(route('orders.payments.proof', [$order, $payment]))->assertForbidden();

        // Admin approves
        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Approved, $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(1, $product->fresh()->sales_count);
        Notification::assertSentTo($customer, PaymentApproved::class);
        Notification::assertSentTo($customer, OrderCompleted::class);

        // Customer can now download
        Storage::disk('local')->put($product->file_path, 'PK-demo-content');
        $response = $this->actingAs($customer)->get(route('downloads.download', $product->slug));
        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('downloads', ['user_id' => $customer->id, 'product_id' => $product->id, 'order_id' => $order->id, 'is_free' => false]);
        $this->assertSame(1, $product->fresh()->download_count);

        // Already owned products cannot be bought again
        $this->post(route('cart.add', $product))->assertSessionHas('error');
    }

    public function test_payment_rejection_shows_reason_and_allows_resubmission(): void
    {
        Notification::fake();
        [$customer, $order, $gateway] = $this->placedOrder();

        $this->actingAs($customer)->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'BAD-TXN-1', 'amount' => $order->total,
        ]);
        $payment = Payment::query()->firstOrFail();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->post(route('admin.payments.reject', $payment), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post(route('admin.payments.reject', $payment), ['reason' => 'Transaction not found in our account.'])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Rejected, $order->payment_status);
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertSame(PaymentRecordStatus::Rejected, $payment->fresh()->status);
        Notification::assertSentTo($customer, PaymentRejected::class);

        $this->actingAs($customer)->get(route('orders.show', $order))->assertSee('Transaction not found in our account.');

        // No downloads after rejection
        $product = $order->items->first()->product;
        $this->get(route('downloads.download', $product->slug))->assertRedirect();
        $this->assertDatabaseCount('downloads', 0);

        // Resubmission works
        $this->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'GOOD-TXN-2', 'amount' => $order->total,
        ])->assertRedirect(route('orders.show', $order));
        $this->assertSame(PaymentStatus::Submitted, $order->fresh()->payment_status);
        $this->assertSame(2, Payment::query()->count());
    }

    public function test_duplicate_transaction_ids_are_rejected(): void
    {
        [$customer, $order, $gateway] = $this->placedOrder();
        [$other, $order2] = $this->placedOrder($gateway);

        $this->actingAs($customer)->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'SAME-123', 'amount' => $order->total,
        ])->assertSessionHasNoErrors();

        $this->actingAs($other)->post(route('orders.pay.store', $order2), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'SAME-123', 'amount' => $order2->total,
        ])->assertSessionHasErrors('transaction_id');
    }

    public function test_customer_cannot_pay_or_view_someone_elses_order(): void
    {
        [, $order, $gateway] = $this->placedOrder();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($intruder)->get(route('orders.pay', $order))->assertForbidden();
        $this->actingAs($intruder)->post(route('orders.pay.store', $order), [
            'payment_gateway_id' => $gateway->id, 'transaction_id' => 'X-1', 'amount' => 1,
        ])->assertForbidden();
    }

    public function test_customer_can_cancel_unpaid_order_and_admin_can_complete_manually(): void
    {
        [$customer, $order] = $this->placedOrder();

        $this->actingAs($customer)->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);

        [$customer2, $order2] = $this->placedOrder();
        $this->actingAs($this->superAdmin())->post(route('admin.orders.status', $order2), ['status' => 'completed'])->assertSessionHas('success');

        $order2->refresh();
        $this->assertSame(OrderStatus::Completed, $order2->status);
        $this->assertSame(PaymentStatus::Approved, $order2->payment_status);
        $this->assertDatabaseHas('download_permissions', ['order_id' => $order2->id, 'user_id' => $customer2->id, 'revoked_at' => null]);

        // Refund revokes access
        $this->post(route('admin.orders.status', $order2), ['status' => 'refunded']);
        $this->assertNotNull($order2->downloadPermissions()->first()->revoked_at);
    }

    /**
     * @return array{0: User, 1: Order, 2: PaymentGateway}
     */
    protected function placedOrder(?PaymentGateway $gateway = null): array
    {
        $product = Product::factory()->published()->create(['price' => 1500]);
        $gateway ??= $this->gateway();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post(route('cart.add', $product));
        $this->post('/checkout', [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '+92 300 0000000',
            'customer_country' => 'Pakistan',
            'payment_gateway_id' => $gateway->id,
            'terms' => '1',
        ]);

        $order = Order::query()->where('user_id', $customer->id)->with('items.product')->latest('id')->firstOrFail();

        return [$customer, $order, $gateway];
    }
}
