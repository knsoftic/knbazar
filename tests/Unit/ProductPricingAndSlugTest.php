<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingAndSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_calculations(): void
    {
        $product = Product::factory()->make(['price' => 2500, 'sale_price' => 1999]);

        $this->assertTrue($product->hasDiscount());
        $this->assertSame(1999.0, $product->finalPrice());
        $this->assertSame(501.0, $product->discountAmount());
        $this->assertSame(20, $product->discountPercent());
    }

    public function test_invalid_sale_price_is_ignored_and_free_products_cost_nothing(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'sale_price' => 1500]);
        $this->assertNull($product->sale_price);
        $this->assertSame(1000.0, (float) $product->effective_price);

        $free = Product::factory()->free()->create(['price' => 999]);
        $this->assertSame(0.0, (float) $free->price);
        $this->assertSame(0.0, $free->finalPrice());
    }

    public function test_slug_generation_is_unique_and_seo_friendly(): void
    {
        $slugs = app(SlugService::class);

        $this->assertSame('premium-wordpress-business-theme', $slugs->unique(Product::class, 'Premium WordPress Business Theme'));

        Product::factory()->create(['slug' => 'premium-wordpress-business-theme']);
        $this->assertSame('premium-wordpress-business-theme-2', $slugs->unique(Product::class, 'Premium WordPress Business Theme'));
    }

    public function test_usernames_avoid_reserved_words(): void
    {
        $username = app(SlugService::class)->uniqueUsername('products@example.com');

        $this->assertNotSame('products', $username);
        $this->assertStringStartsWith('products_', $username);
    }
}
