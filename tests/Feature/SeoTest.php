<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogCache;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_has_meta_tags_and_product_schema(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Premium WordPress Business Theme',
            'slug' => 'premium-wordpress-business-theme',
            'seo_title' => 'Best Business Theme',
            'seo_description' => 'A fast responsive theme.',
            'price' => 2500,
            'sale_price' => 1999,
        ]);

        $response = $this->get('/product/premium-wordpress-business-theme');

        $response->assertOk()
            ->assertSee('<title>Best Business Theme | KN Bazar</title>', false)
            ->assertSee('<meta name="description" content="A fast responsive theme.">', false)
            ->assertSee('<link rel="canonical" href="'.url('/product/premium-wordpress-business-theme').'">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"price":"1999.00"', false)
            ->assertSee('"priceCurrency":"PKR"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false);
    }

    public function test_seo_values_fall_back_to_product_content(): void
    {
        $product = $this->publishedProduct(['name' => 'Fallback Product', 'seo_title' => null, 'seo_description' => null, 'short_description' => 'Short text here.']);

        $this->get($product->url())
            ->assertSee('<title>Fallback Product | KN Bazar</title>', false)
            ->assertSee('content="Short text here."', false);
    }

    public function test_sitemap_lists_only_public_content(): void
    {
        $visible = $this->publishedProduct(['slug' => 'visible-product']);
        $hidden = Product::factory()->pending()->create(['slug' => 'hidden-product']);
        $category = Category::factory()->create(['slug' => 'wordpress-themes']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('/product/visible-product', $response->getContent());
        $this->assertStringNotContainsString('/product/hidden-product', $response->getContent());
        $this->assertStringContainsString('/category/wordpress-themes', $response->getContent());
    }

    public function test_robots_txt_respects_settings(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));

        $this->setting(['seo_robots_index' => false]);
        $this->get('/robots.txt')->assertSee('Disallow: /')->assertDontSee('Sitemap:');
    }

    public function test_clean_urls_for_category_search_and_publisher(): void
    {
        $product = $this->publishedProduct(['name' => 'Searchable WordPress Theme']);
        $product->load('category', 'user');

        $this->get('/category/'.$product->category->slug)->assertOk()->assertSee($product->name);
        $this->get('/search?q=searchable')->assertOk()->assertSee($product->name)->assertSee('noindex', false);
        $this->get('/search?q=nothing-matches-this')->assertOk()->assertSee('No products match');
        $this->get('/publisher/'.$product->user->username)->assertOk()->assertSee($product->name);
    }

    public function test_search_matches_keywords_and_filters(): void
    {
        $theme = $this->publishedProduct(['name' => 'Alpha Product', 'price' => 3000]);
        app(ProductService::class)->syncKeywords($theme, 'responsive wordpress theme');
        $free = Product::factory()->published()->free()->create(['name' => 'Beta Freebie']);

        $this->get('/search?q=responsive')->assertSee('Alpha Product')->assertDontSee('Beta Freebie');
        $this->get('/products?type=free')->assertSee('Beta Freebie')->assertDontSee('Alpha Product');
        $this->get('/products?min_price=2000&max_price=4000')->assertSee('Alpha Product')->assertDontSee('Beta Freebie');
        $this->get('/products?sort=price_desc')->assertOk();
    }

    public function test_inactive_category_hides_its_products(): void
    {
        $product = $this->publishedProduct();
        $product->category->forceFill(['is_active' => false])->save();
        CatalogCache::flush();

        $this->get($product->url())->assertNotFound();
        $this->get('/products')->assertDontSee($product->name);
    }

    public function test_whatsapp_button_and_link(): void
    {
        $this->setting(['whatsapp_enabled' => true, 'whatsapp_country_code' => '92', 'whatsapp_number' => '0300 1234567']);
        $product = $this->publishedProduct(['name' => 'WA Product']);

        $this->get($product->url())->assertSee('https://wa.me/923001234567?text=', false)->assertSee('Order via WhatsApp');

        $this->setting(['whatsapp_enabled' => false]);
        $this->get($product->url())->assertDontSee('wa.me/923001234567', false);
    }
}
