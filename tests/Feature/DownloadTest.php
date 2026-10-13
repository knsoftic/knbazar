<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_download_free_product_and_it_is_tracked(): void
    {
        $product = $this->publishedProduct([], free: true);

        $response = $this->get(route('downloads.download', $product->slug));

        $response->assertOk();
        $this->assertStringContainsString('attachment; filename='.$product->slug, $response->headers->get('content-disposition'));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));
        $this->assertSame(1, $product->fresh()->download_count);
        $this->assertDatabaseHas('downloads', ['product_id' => $product->id, 'user_id' => null, 'is_free' => true, 'ip_address' => '127.0.0.1']);
    }

    public function test_free_download_can_require_login(): void
    {
        $this->setting(['free_download_require_login' => true]);
        $product = $this->publishedProduct([], free: true);

        $this->get(route('downloads.download', $product->slug))->assertRedirect(route('login'));

        $user = User::factory()->customer()->create();
        $this->actingAs($user)->get(route('downloads.download', $product->slug))->assertOk();
        $this->assertDatabaseHas('downloads', ['product_id' => $product->id, 'user_id' => $user->id]);
    }

    public function test_free_downloads_can_be_disabled(): void
    {
        $this->setting(['free_downloads_enabled' => false]);
        $product = $this->publishedProduct([], free: true);

        $this->get(route('downloads.download', $product->slug))
            ->assertRedirect(route('products.show', $product->slug))
            ->assertSessionHas('error', 'Free downloads are currently disabled.');
        $this->assertDatabaseCount('downloads', 0);
    }

    public function test_unpublished_free_product_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $product = Product::factory()->free()->pending()->create();

        $this->get(route('downloads.download', $product->slug))->assertRedirect();
        $this->assertDatabaseCount('downloads', 0);
    }

    public function test_paid_product_requires_login_and_purchase(): void
    {
        $product = $this->publishedProduct();

        $this->get(route('downloads.download', $product->slug))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('downloads.download', $product->slug))
            ->assertRedirect(route('products.show', $product->slug))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('downloads', 0);
    }

    public function test_download_limit_is_enforced(): void
    {
        $this->setting(['download_limit_per_purchase' => 1]);
        $product = $this->publishedProduct();
        $customer = User::factory()->customer()->create();
        $this->grantPurchase($customer, $product);

        $this->actingAs($customer)->get(route('downloads.download', $product->slug))->assertOk();
        $this->get(route('downloads.download', $product->slug))->assertSessionHas('error', 'You have reached the download limit for this product.');
    }

    public function test_product_files_are_not_publicly_accessible(): void
    {
        $product = $this->publishedProduct();

        // Files live on the private disk; no URL maps to them.
        $this->assertStringStartsWith('products/files/', $product->file_path);
        $this->assertFalse(Storage::disk('public')->exists($product->file_path));
        $this->get('/storage/'.$product->file_path)->assertNotFound();
        $this->get('/storage/app/private/'.$product->file_path)->assertNotFound();
    }

    public function test_customer_downloads_page_lists_purchases(): void
    {
        $product = $this->publishedProduct();
        $customer = User::factory()->customer()->create();
        $this->grantPurchase($customer, $product);

        $this->actingAs($customer)->get('/downloads')->assertOk()->assertSee($product->name)->assertSee('Active');
        $this->get('/account/purchases')->assertOk()->assertSee($product->name);
    }

    protected function grantPurchase(User $customer, Product $product): void
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-'.$customer->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'subtotal' => $product->price,
            'total' => $product->price,
            'currency' => 'PKR',
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'publisher_id' => $product->user_id, 'product_name' => $product->name,
            'unit_price' => $product->price, 'sale_price' => $product->price, 'quantity' => 1, 'total' => $product->price,
        ]);

        app(OrderService::class)->markCompleted($order, $this->superAdmin());
    }
}
