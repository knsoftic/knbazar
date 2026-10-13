<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\WhatsAppOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_message_from_template_and_url(): void
    {
        $this->setting([
            'whatsapp_enabled' => true,
            'whatsapp_country_code' => '92',
            'whatsapp_number' => '03001234567',
            'whatsapp_message_template' => "Hello, I want to purchase this product:\nProduct: {product_name}\nProduct ID: {product_id}\nPrice: {price}\nProduct URL: {product_url}",
        ]);
        $product = Product::factory()->published()->create(['name' => 'Theme X', 'slug' => 'theme-x', 'price' => 2500, 'sale_price' => 1999]);
        $service = app(WhatsAppOrderService::class);

        $this->assertSame('923001234567', $service->phoneNumber());
        $this->assertSame(
            "Hello, I want to purchase this product:\nProduct: Theme X\nProduct ID: {$product->id}\nPrice: Rs 1,999\nProduct URL: ".url('/product/theme-x'),
            $service->message($product)
        );
        $this->assertStringStartsWith('https://wa.me/923001234567?text=Hello%2C%20I%20want', $service->url($product));
        $this->assertTrue($service->isAvailableFor($product));
    }

    public function test_number_already_containing_country_code_is_not_duplicated(): void
    {
        $this->setting(['whatsapp_country_code' => '92', 'whatsapp_number' => '+92 300 1234567']);

        $this->assertSame('923001234567', app(WhatsAppOrderService::class)->phoneNumber());
    }

    public function test_availability_respects_global_product_and_free_flags(): void
    {
        $this->setting(['whatsapp_enabled' => true, 'whatsapp_number' => '3001234567']);
        $service = app(WhatsAppOrderService::class);

        $this->assertFalse($service->isAvailableFor(Product::factory()->published()->free()->create()));
        $this->assertFalse($service->isAvailableFor(Product::factory()->published()->create(['whatsapp_enabled' => false])));
        $this->assertFalse($service->isAvailableFor(Product::factory()->pending()->create()));

        $this->setting(['whatsapp_number' => '']);
        $this->assertFalse($service->isAvailableFor(Product::factory()->published()->create()));
    }
}
