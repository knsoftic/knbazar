<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PublisherProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\CatalogCache;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo marketplace content. Credentials are documented in README.md.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(SettingService $settings, ProductService $productService, OrderService $orders): void
    {
        $settings->setMany([
            'site_name' => 'KN Bazar',
            'contact_email' => 'support@knbazar.test',
            'contact_phone' => '+92 300 1234567',
            'contact_address' => 'Lahore, Pakistan',
            'whatsapp_enabled' => true,
            'whatsapp_country_code' => '92',
            'whatsapp_number' => '3001234567',
        ]);

        // ------------------------------------------------------------------
        // Accounts
        // ------------------------------------------------------------------
        $super = $this->user('Super Admin', 'superadmin', 'superadmin@knbazar.test', RoleName::SuperAdmin);
        $admin = $this->user('Catalog Admin', 'catalogadmin', 'admin@knbazar.test', RoleName::Admin);
        $admin->permissions()->sync(Permission::query()->whereIn('slug', [
            'manage_products', 'approve_products', 'manage_categories', 'manage_orders',
            'manage_payments', 'manage_downloads', 'view_reports', 'manage_messages',
        ])->pluck('id'));

        $publisher = $this->user('Ayesha Khan', 'pixelcraft', 'publisher@knbazar.test', RoleName::Publisher);
        $this->publisherProfile($publisher, 'PixelCraft Studio', PublisherStatus::Approved, 'We design clean, conversion-focused WordPress themes and UI kits used by thousands of businesses.');

        $publisher2 = $this->user('Bilal Ahmed', 'codenest', 'publisher2@knbazar.test', RoleName::Publisher);
        $this->publisherProfile($publisher2, 'CodeNest', PublisherStatus::Approved, 'Laravel and PHP scripts, admin dashboards and developer tools.');

        $pending = $this->user('Sara Malik', 'sarafonts', 'pending.publisher@knbazar.test', RoleName::Publisher);
        $this->publisherProfile($pending, 'Sara Fonts', PublisherStatus::Pending, 'Handmade display fonts.');

        $customer = $this->user('Usman Ali', 'usman', 'customer@knbazar.test', RoleName::Customer);

        // ------------------------------------------------------------------
        // Categories
        // ------------------------------------------------------------------
        $categoryData = [
            ['WordPress Themes', 'wordpress-themes', 'bi-wordpress', [37, 99, 235], 'Premium and free WordPress themes for blogs, businesses and stores.'],
            ['Plugins', 'plugins', 'bi-plug', [124, 58, 237], 'WordPress and web plugins that add powerful features to your site.'],
            ['PHP Scripts', 'php-scripts', 'bi-code-slash', [5, 150, 105], 'Ready-to-run PHP and Laravel applications and scripts.'],
            ['HTML Templates', 'html-templates', 'bi-filetype-html', [234, 88, 12], 'Responsive HTML5/CSS3 landing pages and website templates.'],
            ['Graphics', 'graphics', 'bi-palette', [219, 39, 119], 'Logos, icons, mockups and UI kits.'],
            ['eBooks', 'ebooks', 'bi-book', [8, 145, 178], 'Guides and eBooks on design, development and business.'],
            ['Fonts', 'fonts', 'bi-fonts', [71, 85, 105], 'Display and text fonts for print and web.'],
        ];

        $categories = [];
        foreach ($categoryData as $i => [$name, $slug, $icon, $color, $description]) {
            $category = Category::query()->firstOrNew(['slug' => $slug]);
            $category->fill([
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'seo_title' => $name.' - Download Premium & Free '.$name,
                'seo_description' => $description,
                'is_active' => true,
                'sort_order' => $i,
            ]);
            $category->image ??= DemoMedia::categoryImage($name, $color);
            $category->save();
            $categories[$slug] = ['model' => $category, 'color' => $color];
        }

        // ------------------------------------------------------------------
        // Payment gateways
        // ------------------------------------------------------------------
        $gateways = [
            ['JazzCash', 'Instant mobile wallet transfer.', [['label' => 'Account Title', 'value' => 'KN Bazar'], ['label' => 'JazzCash Number', 'value' => '0300 1234567']], 'PKR', true],
            ['EasyPaisa', 'Pay from your EasyPaisa wallet.', [['label' => 'Account Title', 'value' => 'KN Bazar'], ['label' => 'EasyPaisa Number', 'value' => '0345 7654321']], 'PKR', true],
            ['Bank Transfer', 'Transfer from any Pakistani bank.', [['label' => 'Bank Name', 'value' => 'Meezan Bank'], ['label' => 'Account Title', 'value' => 'KN Bazar Pvt Ltd'], ['label' => 'Account Number', 'value' => '0101 0123456789'], ['label' => 'IBAN', 'value' => 'PK36MEZN0001010123456789']], 'PKR', true],
            ['USDT (TRC20)', 'Pay with USDT on the TRON network.', [['label' => 'Network', 'value' => 'TRC20'], ['label' => 'Wallet Address', 'value' => 'TXYZdemoWalletAddress1234567890abc']], 'PKR', false],
        ];

        foreach ($gateways as $i => [$name, $description, $details, $currency, $proof]) {
            PaymentGateway::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'driver' => 'manual',
                'description' => $description,
                'instructions' => "1. Send the exact order total to the account below.\n2. Use your order number as the payment reference.\n3. Submit the transaction ID".($proof ? ' and a screenshot' : '')." on the payment page.\n4. We verify payments within a few hours.",
                'details' => $details,
                'currency' => $currency,
                'requires_proof' => $proof,
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        // ------------------------------------------------------------------
        // Pages
        // ------------------------------------------------------------------
        $this->call(PageSeeder::class);

        // ------------------------------------------------------------------
        // Products
        // ------------------------------------------------------------------
        $products = [
            // [name, category, owner, type, free, price, sale, featured, keywords, short]
            ['Premium WordPress Business Theme', 'wordpress-themes', $publisher, 'theme', false, 2500, 1999, true, 'wordpress theme, business theme, responsive wordpress theme, premium wordpress template', 'A fast, responsive WordPress theme for agencies, startups and corporate websites.'],
            ['Starter Blog Theme', 'wordpress-themes', $publisher, 'theme', true, 0, null, true, 'free wordpress theme, blog theme, minimal theme', 'A clean, minimal and lightning fast blog theme - free forever.'],
            ['WooCommerce Storefront Pro', 'wordpress-themes', $publisher, 'theme', false, 4500, null, false, 'woocommerce theme, shop theme, ecommerce wordpress', 'Conversion-optimised WooCommerce theme with mega menus and quick view.'],
            ['SEO Booster Plugin', 'plugins', $publisher2, 'plugin', false, 1800, 1500, true, 'seo plugin, schema markup, sitemap plugin', 'Automatic schema markup, XML sitemaps and meta tags for WordPress.'],
            ['Contact Form Lite', 'plugins', $publisher2, 'plugin', true, 0, null, false, 'contact form, free plugin, form builder', 'Drag-and-drop contact forms with spam protection.'],
            ['Laravel Admin Dashboard', 'php-scripts', $publisher2, 'script', false, 6000, 4999, true, 'laravel admin, dashboard, php script, admin panel', 'A complete Laravel admin panel with roles, charts and CRUD generators.'],
            ['Invoice Manager Script', 'php-scripts', $publisher2, 'script', false, 3500, null, false, 'invoice script, billing, php invoice', 'Create, send and track invoices with recurring billing and PDF export.'],
            ['SaaS Landing Page Template', 'html-templates', $publisher, 'template', false, 1200, 899, false, 'landing page, html template, saas template, bootstrap template', 'Modern HTML5 landing page template for SaaS and app products.'],
            ['Portfolio HTML Template', 'html-templates', $publisher, 'template', true, 0, null, false, 'portfolio template, free html template, personal website', 'Free one-page portfolio template for designers and developers.'],
            ['Flat Icon Pack (500 icons)', 'graphics', $publisher, 'graphics', false, 900, null, false, 'icons, icon pack, svg icons, ui icons', '500 pixel-perfect flat icons in SVG, PNG and icon font formats.'],
            ['Social Media Templates Kit', 'graphics', $publisher, 'graphics', true, 0, null, true, 'social media templates, instagram templates, canva', 'Free editable templates for Instagram, Facebook and LinkedIn posts.'],
            ['Freelancing Success eBook', 'ebooks', $publisher2, 'ebook', false, 700, 499, false, 'freelancing ebook, fiverr, upwork guide', 'A practical guide to landing high-paying freelance clients.'],
            ['Laravel Beginner Guide', 'ebooks', $publisher2, 'ebook', true, 0, null, false, 'laravel ebook, php guide, free ebook', 'A free beginner-friendly introduction to building apps with Laravel.'],
            ['Modern Sans Font Family', 'fonts', $publisher, 'font', false, 1500, null, false, 'sans serif font, font family, web font', 'A versatile sans serif family with 8 weights and full Latin support.'],
        ];

        $created = [];
        foreach ($products as $i => [$name, $cat, $owner, $type, $free, $price, $sale, $featured, $keywords, $short]) {
            $slug = Str::slug($name);
            if ($existing = Product::query()->where('slug', $slug)->first()) {
                $created[$slug] = $existing;

                continue;
            }

            $color = $categories[$cat]['color'];
            $image = DemoMedia::productImage($name, config('marketplace.product_types.'.$type).($free ? ' - FREE' : ''), $color, [max(0, $color[0] - 60), max(0, $color[1] - 60), min(255, $color[2] + 40)]);
            $file = DemoMedia::productFile($name, '1.'.($i % 4).'.0');

            $product = new Product([
                'category_id' => $categories[$cat]['model']->getKey(),
                'name' => $name,
                'slug' => $slug,
                'short_description' => $short,
                'description' => $this->description($name, $short),
                'features' => "Fully responsive on desktop, tablet and mobile\nClean, well-commented code\nDetailed documentation\nFree lifetime updates\nFriendly support",
                'requirements' => $type === 'ebook' ? 'Any PDF reader' : ($type === 'font' ? 'Windows, macOS or Linux' : "PHP 8.2 or higher\nMySQL 5.7+ / MariaDB 10.3+"),
                'product_type' => $type,
                'is_free' => $free,
                'price' => $price,
                'sale_price' => $sale,
                'version' => '1.'.($i % 4).'.0',
                'demo_url' => 'https://example.com/demo/'.$slug,
                'documentation_url' => 'https://example.com/docs/'.$slug,
                'seo_title' => $name.($free ? ' - Free Download' : ''),
                'seo_description' => $short,
                'focus_keyword' => Str::before($keywords, ','),
                'whatsapp_enabled' => true,
            ]);

            $publishedAt = now()->subDays(30 - $i * 2);
            $product->forceFill([
                'user_id' => $owner->getKey(),
                'image' => $image['path'],
                'thumbnail' => $image['thumb'],
                'file_path' => $file['path'],
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'file_type' => $file['type'],
                'file_mime' => $file['mime'],
                'is_featured' => $featured,
                'status' => ProductStatus::Published,
                'approval_status' => ApprovalStatus::Approved,
                'submitted_at' => $publishedAt->copy()->subDay(),
                'approved_at' => $publishedAt,
                'approved_by' => $super->getKey(),
                'published_at' => $publishedAt,
                'view_count' => random_int(40, 900),
                'download_count' => $free ? random_int(10, 300) : 0,
                'created_at' => $publishedAt->copy()->subDay(),
            ])->save();

            $productService->syncKeywords($product, $keywords);
            $product->statusHistories()->create(['user_id' => $super->getKey(), 'from_status' => 'pending', 'to_status' => 'published', 'note' => 'Approved and published']);

            $created[$slug] = $product;
        }

        // Workflow examples for the demo publisher.
        $this->workflowProduct('Restaurant Menu Theme', $publisher, $categories['wordpress-themes'], ProductStatus::Pending, ApprovalStatus::Pending, null);
        $this->workflowProduct('Crypto Dashboard UI Kit', $publisher, $categories['graphics'], ProductStatus::Rejected, ApprovalStatus::Rejected, 'Please add at least three preview screenshots and a documentation link.');
        $this->workflowProduct('Wedding Invitation Template', $publisher, $categories['html-templates'], ProductStatus::Draft, ApprovalStatus::Draft, null);

        // ------------------------------------------------------------------
        // Demo orders for the customer
        // ------------------------------------------------------------------
        if (! Order::query()->where('user_id', $customer->getKey())->exists()) {
            $bank = PaymentGateway::query()->where('slug', 'bank-transfer')->first();
            $jazz = PaymentGateway::query()->where('slug', 'jazzcash')->first();

            // 1) Completed (paid) order -> downloads available.
            $paid = $this->order($customer, $bank, [$created['premium-wordpress-business-theme'], $created['seo-booster-plugin']], now()->subDays(5));
            $payment = $paid->payments()->create([
                'payment_gateway_id' => $bank->getKey(),
                'user_id' => $customer->getKey(),
                'amount' => $paid->total,
                'currency' => $paid->currency,
                'transaction_id' => 'MEZN'.random_int(10000000, 99999999),
                'sender_name' => $customer->name,
                'sender_account' => 'PK00 DEMO 0000 1111',
                'status' => PaymentRecordStatus::Approved,
                'reviewed_by' => $super->getKey(),
                'reviewed_at' => now()->subDays(4),
            ]);
            $paid->forceFill([
                'status' => OrderStatus::Completed,
                'payment_status' => PaymentStatus::Approved,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => now()->subDays(4),
                'completed_at' => now()->subDays(4),
            ])->save();
            $orders->recordHistory($paid, OrderStatus::PaymentSubmitted, OrderStatus::Completed, $super, 'Payment approved - order completed');
            $orders->grantDownloadPermissions($paid);
            $orders->incrementSales($paid);

            // 2) Payment submitted, waiting for admin verification.
            $review = $this->order($customer, $jazz, [$created['laravel-admin-dashboard']], now()->subDay());
            $review->payments()->create([
                'payment_gateway_id' => $jazz->getKey(),
                'user_id' => $customer->getKey(),
                'amount' => $review->total,
                'currency' => $review->currency,
                'transaction_id' => 'JC'.random_int(100000000, 999999999),
                'sender_name' => $customer->name,
                'sender_account' => '0300 0000000',
                'notes' => 'Paid from my JazzCash wallet.',
                'status' => PaymentRecordStatus::UnderReview,
            ]);
            $review->forceFill(['status' => OrderStatus::PaymentSubmitted, 'payment_status' => PaymentStatus::Submitted])->save();
            $orders->recordHistory($review, OrderStatus::AwaitingPayment, OrderStatus::PaymentSubmitted, $customer, 'Payment details submitted');

            // 3) Awaiting payment.
            $this->order($customer, $bank, [$created['invoice-manager-script']], now()->subHours(3));
        }

        Banner::query()->firstOrCreate(['title' => 'New: Laravel Admin Dashboard'], [
            'subtitle' => 'Launch your next SaaS faster with roles, charts and CRUD generators built in. Now 17% off.',
            'button_text' => 'View product',
            'button_url' => '/product/laravel-admin-dashboard',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        CatalogCache::flush();
    }

    protected function user(string $name, string $username, string $email, RoleName $role): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'username' => $username, 'phone' => '+92 300 1234567', 'country' => 'Pakistan']);
        $user->forceFill([
            'role_id' => Role::idFor($role),
            'password' => Hash::make(self::PASSWORD),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'deleted_at' => null,
        ])->save();

        return $user;
    }

    protected function publisherProfile(User $user, string $displayName, PublisherStatus $status, string $bio): void
    {
        $profile = PublisherProfile::query()->firstOrNew(['user_id' => $user->getKey()]);
        $profile->fill(['display_name' => $displayName, 'bio' => $bio, 'country' => 'Pakistan', 'website' => 'https://example.com', 'whatsapp' => '+92 300 1234567']);
        $profile->forceFill([
            'user_id' => $user->getKey(),
            'status' => $status,
            'approved_at' => $status === PublisherStatus::Approved ? now() : null,
        ])->save();
    }

    /**
     * @param  array{model: Category, color: array<int, int>}  $category
     */
    protected function workflowProduct(string $name, User $owner, array $category, ProductStatus $status, ApprovalStatus $approval, ?string $reason): void
    {
        $slug = Str::slug($name);
        if (Product::query()->where('slug', $slug)->exists()) {
            return;
        }

        $color = $category['color'];
        $image = DemoMedia::productImage($name, 'Template', $color, [max(0, $color[0] - 60), max(0, $color[1] - 60), min(255, $color[2] + 40)]);
        $file = DemoMedia::productFile($name, '1.0.0');

        $product = new Product([
            'category_id' => $category['model']->getKey(),
            'name' => $name,
            'slug' => $slug,
            'short_description' => 'Demo product showing the "'.$status->label().'" workflow state.',
            'description' => $this->description($name, 'A product used to demonstrate the review workflow.'),
            'product_type' => 'template',
            'is_free' => false,
            'price' => 1500,
            'version' => '1.0.0',
            'whatsapp_enabled' => true,
        ]);
        $product->forceFill([
            'user_id' => $owner->getKey(),
            'image' => $image['path'],
            'thumbnail' => $image['thumb'],
            'file_path' => $file['path'],
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'file_type' => $file['type'],
            'file_mime' => $file['mime'],
            'status' => $status,
            'approval_status' => $approval,
            'rejection_reason' => $reason,
            'submitted_at' => $approval === ApprovalStatus::Draft ? null : now()->subDays(2),
        ])->save();

        $product->statusHistories()->create(['user_id' => $owner->getKey(), 'from_status' => null, 'to_status' => 'draft', 'note' => 'Created']);
        if ($approval !== ApprovalStatus::Draft) {
            $product->statusHistories()->create(['user_id' => $owner->getKey(), 'from_status' => 'draft', 'to_status' => 'pending', 'note' => 'Submitted for review']);
        }
        if ($reason) {
            $product->statusHistories()->create(['user_id' => null, 'from_status' => 'pending', 'to_status' => 'rejected', 'note' => $reason]);
        }
    }

    /**
     * @param  array<int, Product>  $products
     */
    protected function order(User $customer, PaymentGateway $gateway, array $products, $date): Order
    {
        $subtotal = collect($products)->sum(fn (Product $p) => (float) $p->price);
        $total = collect($products)->sum(fn (Product $p) => $p->finalPrice());

        $order = Order::query()->create([
            'order_number' => 'TMP-'.Str::uuid(),
            'user_id' => $customer->getKey(),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'customer_country' => 'Pakistan',
            'subtotal' => $subtotal,
            'discount_total' => $subtotal - $total,
            'total' => $total,
            'currency' => 'PKR',
            'payment_gateway_id' => $gateway->getKey(),
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $order->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
        $order->forceFill(['order_number' => app(OrderService::class)->orderNumberFor($order)])->save();

        foreach ($products as $product) {
            $order->items()->create([
                'product_id' => $product->getKey(),
                'publisher_id' => $product->user_id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'unit_price' => $product->price,
                'sale_price' => $product->finalPrice(),
                'discount' => $product->discountAmount(),
                'quantity' => 1,
                'total' => $product->finalPrice(),
            ]);
        }

        $order->statusHistories()->create(['user_id' => $customer->getKey(), 'from_status' => null, 'to_status' => OrderStatus::AwaitingPayment->value, 'note' => 'Order placed']);

        return $order;
    }

    protected function description(string $name, string $short): string
    {
        return <<<MD
        ## Overview

        **{$name}** - {$short}

        Built with performance and accessibility in mind, it works great on every screen size and is easy to customise.

        ## What's included

        - Main package with source files
        - Documentation and setup guide
        - Six months of free support

        ## Support

        Questions? Contact the publisher through the marketplace or read the documentation linked on this page.
        MD;
    }
}
