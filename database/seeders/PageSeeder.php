<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Default static pages (About, Contact, Terms, Privacy, Refund, Publisher
 * Guidelines, How It Works). Safe for production: existing pages are never
 * overwritten, so admins can edit them freely afterwards.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $site = (string) setting('site_name', 'KN Bazar');

        $pages = [
            ['About Us', 'about', "## Who we are\n\n{$site} is a marketplace for premium and free digital products - themes, plugins, scripts, templates, graphics, eBooks and fonts - created by independent publishers.\n\n## Our promise\n\n- Every product is reviewed by our team before it is published.\n- Secure checkout with local payment methods.\n- Instant downloads once your payment is verified."],
            ['Contact', 'contact', "Have a question about a product, an order or publishing on {$site}? Send us a message using the form and we will reply within one business day."],
            ['Terms & Conditions', 'terms-and-conditions', "## 1. Introduction\n\nBy using {$site} you agree to these terms.\n\n## 2. Purchases\n\nDigital products are licensed, not sold. Each purchase grants a licence to use the product as described on its page.\n\n## 3. Accounts\n\nYou are responsible for keeping your account secure.\n\n## 4. Publishers\n\nPublishers must own the rights to everything they upload."],
            ['Privacy Policy', 'privacy-policy', "## What we collect\n\nWe collect the information you give us when you register, place an order or contact us, and basic technical data such as your IP address for security.\n\n## How we use it\n\nTo process orders, deliver downloads, prevent fraud and support you. We never sell your data."],
            ['Refund Policy', 'refund-policy', "Because digital products can be downloaded immediately, purchases are generally non-refundable.\n\nWe will issue a refund when a product is materially different from its description, or broken and the publisher cannot fix it within a reasonable time. Contact us within 14 days of purchase."],
            ['Publisher Guidelines', 'publisher-guidelines', "## Before you submit\n\n- You must own all rights to the product and its assets.\n- Provide a clear main image, accurate description and working demo/documentation links.\n- Upload the complete product as a ZIP or supported file type.\n\n## Review\n\nEvery submission is reviewed. If a product is rejected you will see the reason, can edit it and resubmit."],
            ['How It Works', 'how-it-works', "## For customers\n\n1. Browse or search for a product.\n2. Download free products instantly, or add paid products to your cart.\n3. Pay with JazzCash, EasyPaisa, bank transfer or USDT and submit your transaction ID.\n4. Once verified, your downloads unlock in your account.\n\n## For publishers\n\n1. Register as a publisher.\n2. Upload your product and submit it for review.\n3. Once approved it goes live and you can track sales and downloads."],
        ];

        foreach ($pages as $i => [$title, $slug, $content]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => $content,
                'seo_title' => $title,
                'seo_description' => Str::limit(strip_tags(str_replace('#', '', $content)), 155),
                'is_active' => true,
                'show_in_footer' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
