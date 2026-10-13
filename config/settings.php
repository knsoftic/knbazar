<?php

/*
|--------------------------------------------------------------------------
| Runtime settings definitions
|--------------------------------------------------------------------------
|
| Every admin-editable setting is declared here with its group, type,
| default value and validation rules. Values saved by administrators are
| stored in the `settings` table and override these defaults. The admin
| settings screens and their validation are generated from this file, so
| adding a new setting only requires a new entry below.
|
| Supported types: string, text, email, url, bool, int, image, select, timezone
|
*/

return [

    'groups' => [
        'general' => ['label' => 'General', 'icon' => 'bi-gear'],
        'social' => ['label' => 'Social Links', 'icon' => 'bi-share'],
        'registration' => ['label' => 'Registration', 'icon' => 'bi-person-plus'],
        'publisher' => ['label' => 'Publishers', 'icon' => 'bi-shop'],
        'checkout' => ['label' => 'Cart & Checkout', 'icon' => 'bi-cart'],
        'downloads' => ['label' => 'Downloads', 'icon' => 'bi-download'],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'bi-whatsapp'],
        'uploads' => ['label' => 'Uploads', 'icon' => 'bi-cloud-upload'],
        'homepage' => ['label' => 'Homepage', 'icon' => 'bi-house'],
        'notifications' => ['label' => 'Notifications', 'icon' => 'bi-bell'],
        'seo' => ['label' => 'SEO', 'icon' => 'bi-search'],
    ],

    'definitions' => [

        // General
        'site_name' => ['group' => 'general', 'type' => 'string', 'label' => 'Website name', 'default' => 'KN Bazar', 'rules' => 'required|string|max:100'],
        'site_tagline' => ['group' => 'general', 'type' => 'string', 'label' => 'Tagline', 'default' => 'Premium & free digital products', 'rules' => 'nullable|string|max:160'],
        'site_logo' => ['group' => 'general', 'type' => 'image', 'label' => 'Logo', 'default' => null, 'help' => 'PNG, JPG or WEBP. Recommended height 40-60px.'],
        'site_favicon' => ['group' => 'general', 'type' => 'image', 'label' => 'Favicon', 'default' => null, 'help' => 'Square PNG or ICO, 32x32 or larger.', 'accept' => 'png,ico'],
        'contact_email' => ['group' => 'general', 'type' => 'email', 'label' => 'Contact email', 'default' => 'support@example.com', 'rules' => 'nullable|email|max:150'],
        'contact_phone' => ['group' => 'general', 'type' => 'string', 'label' => 'Contact phone', 'default' => '', 'rules' => 'nullable|string|max:40'],
        'contact_address' => ['group' => 'general', 'type' => 'text', 'label' => 'Address', 'default' => '', 'rules' => 'nullable|string|max:500'],
        'currency' => ['group' => 'general', 'type' => 'select', 'label' => 'Currency', 'default' => 'PKR', 'options' => 'currencies'],
        'currency_position' => ['group' => 'general', 'type' => 'select', 'label' => 'Currency symbol position', 'default' => 'before', 'options' => ['before' => 'Before amount (Rs 1,999)', 'after' => 'After amount (1,999 Rs)']],
        'timezone' => ['group' => 'general', 'type' => 'timezone', 'label' => 'Timezone', 'default' => 'Asia/Karachi'],
        'footer_text' => ['group' => 'general', 'type' => 'text', 'label' => 'Footer text', 'default' => 'A modern marketplace for digital products.', 'rules' => 'nullable|string|max:500'],
        'copyright_text' => ['group' => 'general', 'type' => 'string', 'label' => 'Copyright line', 'default' => 'All rights reserved.', 'rules' => 'nullable|string|max:200'],

        // Social
        'social_facebook' => ['group' => 'social', 'type' => 'url', 'label' => 'Facebook URL', 'default' => ''],
        'social_twitter' => ['group' => 'social', 'type' => 'url', 'label' => 'X / Twitter URL', 'default' => ''],
        'social_instagram' => ['group' => 'social', 'type' => 'url', 'label' => 'Instagram URL', 'default' => ''],
        'social_youtube' => ['group' => 'social', 'type' => 'url', 'label' => 'YouTube URL', 'default' => ''],
        'social_linkedin' => ['group' => 'social', 'type' => 'url', 'label' => 'LinkedIn URL', 'default' => ''],
        'social_tiktok' => ['group' => 'social', 'type' => 'url', 'label' => 'TikTok URL', 'default' => ''],
        'social_github' => ['group' => 'social', 'type' => 'url', 'label' => 'GitHub URL', 'default' => ''],

        // Registration
        'registration_enabled' => ['group' => 'registration', 'type' => 'bool', 'label' => 'Allow customer registration', 'default' => true],
        'email_verification_required' => ['group' => 'registration', 'type' => 'bool', 'label' => 'Require email verification before checkout, downloads and product submission', 'default' => false, 'help' => 'Requires working mail configuration in .env.'],

        // Publisher
        'publisher_registration_enabled' => ['group' => 'publisher', 'type' => 'bool', 'label' => 'Allow publisher registration', 'default' => true],
        'publisher_require_approval' => ['group' => 'publisher', 'type' => 'bool', 'label' => 'New publisher accounts require admin approval', 'default' => true, 'help' => 'When enabled, new publishers can sign in and complete their profile but cannot submit products until approved.'],
        'publisher_max_pending_products' => ['group' => 'publisher', 'type' => 'int', 'label' => 'Max products pending review per publisher (0 = unlimited)', 'default' => 0, 'rules' => 'required|integer|min:0|max:1000'],

        // Checkout
        'checkout_require_address' => ['group' => 'checkout', 'type' => 'bool', 'label' => 'Require billing address at checkout', 'default' => false],
        'checkout_terms_page' => ['group' => 'checkout', 'type' => 'string', 'label' => 'Terms page slug shown at checkout', 'default' => 'terms-and-conditions', 'rules' => 'nullable|string|max:150|alpha_dash'],
        'cart_allow_quantity' => ['group' => 'checkout', 'type' => 'bool', 'label' => 'Allow quantity greater than 1 (multiple licences)', 'default' => false],
        'cart_max_quantity' => ['group' => 'checkout', 'type' => 'int', 'label' => 'Maximum quantity per product', 'default' => 5, 'rules' => 'required|integer|min:1|max:100'],
        'order_cancel_unpaid_allowed' => ['group' => 'checkout', 'type' => 'bool', 'label' => 'Customers may cancel unpaid orders', 'default' => true],

        // Downloads
        'free_downloads_enabled' => ['group' => 'downloads', 'type' => 'bool', 'label' => 'Enable free product downloads', 'default' => true],
        'free_download_require_login' => ['group' => 'downloads', 'type' => 'bool', 'label' => 'Require login for free downloads', 'default' => false],
        'download_limit_per_purchase' => ['group' => 'downloads', 'type' => 'int', 'label' => 'Download limit per purchase (0 = unlimited)', 'default' => 0, 'rules' => 'required|integer|min:0|max:10000'],
        'download_expiry_days' => ['group' => 'downloads', 'type' => 'int', 'label' => 'Download access expires after N days (0 = never)', 'default' => 0, 'rules' => 'required|integer|min:0|max:36500'],

        // WhatsApp
        'whatsapp_enabled' => ['group' => 'whatsapp', 'type' => 'bool', 'label' => 'Enable "Order via WhatsApp" globally', 'default' => true],
        'whatsapp_country_code' => ['group' => 'whatsapp', 'type' => 'string', 'label' => 'Country code (digits only, e.g. 92)', 'default' => '92', 'rules' => 'nullable|digits_between:1,4'],
        'whatsapp_number' => ['group' => 'whatsapp', 'type' => 'string', 'label' => 'WhatsApp number (without country code)', 'default' => '', 'rules' => 'nullable|regex:/^[0-9 ]{5,20}$/'],
        'whatsapp_button_text' => ['group' => 'whatsapp', 'type' => 'string', 'label' => 'Button text', 'default' => 'Order via WhatsApp', 'rules' => 'required|string|max:60'],
        'whatsapp_message_template' => ['group' => 'whatsapp', 'type' => 'text', 'label' => 'Order message template', 'default' => "Hello, I want to purchase this product:\nProduct: {product_name}\nProduct ID: {product_id}\nPrice: {price}\nProduct URL: {product_url}", 'rules' => 'required|string|max:1000', 'help' => 'Placeholders: {product_name}, {product_id}, {price}, {regular_price}, {product_url}, {category}, {publisher}, {site_name}'],

        // Uploads
        'upload_image_types' => ['group' => 'uploads', 'type' => 'string', 'label' => 'Allowed image formats', 'default' => 'jpg,jpeg,png,webp', 'rules' => 'required|string|max:100|regex:/^[a-z0-9]+(,[a-z0-9]+)*$/', 'help' => 'Comma-separated. Supported: jpg, jpeg, png, webp, gif.'],
        'upload_image_max_kb' => ['group' => 'uploads', 'type' => 'int', 'label' => 'Max image size (KB)', 'default' => 2048, 'rules' => 'required|integer|min:100|max:20480'],
        'upload_gallery_max' => ['group' => 'uploads', 'type' => 'int', 'label' => 'Max gallery images per product', 'default' => 8, 'rules' => 'required|integer|min:0|max:30'],
        'upload_file_types' => ['group' => 'uploads', 'type' => 'string', 'label' => 'Allowed product file formats', 'default' => 'zip,rar,7z,pdf,epub,psd,ai,eps,txt,docx,xlsx,pptx,mp3,mp4,ttf,otf,woff,woff2,png,jpg', 'rules' => 'required|string|max:300|regex:/^[a-z0-9]+(,[a-z0-9]+)*$/', 'help' => 'Comma-separated. Executable and script formats are always blocked.'],
        'upload_file_max_mb' => ['group' => 'uploads', 'type' => 'int', 'label' => 'Max product file size (MB)', 'default' => 100, 'rules' => 'required|integer|min:1|max:2048', 'help' => 'PHP upload_max_filesize and post_max_size must also allow this size.'],

        // Homepage
        'home_hero_title' => ['group' => 'homepage', 'type' => 'string', 'label' => 'Hero title', 'default' => 'Discover premium & free digital products', 'rules' => 'nullable|string|max:150'],
        'home_hero_subtitle' => ['group' => 'homepage', 'type' => 'text', 'label' => 'Hero subtitle', 'default' => 'Themes, plugins, templates, graphics, eBooks and more from independent publishers.', 'rules' => 'nullable|string|max:300'],
        'home_products_per_section' => ['group' => 'homepage', 'type' => 'int', 'label' => 'Products per section', 'default' => 8, 'rules' => 'required|integer|min:2|max:24'],
        'home_show_categories' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show categories', 'default' => true],
        'home_show_featured' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show featured products', 'default' => true],
        'home_show_latest' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show latest products', 'default' => true],
        'home_show_free' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show free products', 'default' => true],
        'home_show_paid' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show paid products', 'default' => true],
        'home_show_popular' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show popular products', 'default' => true],
        'home_show_publisher_cta' => ['group' => 'homepage', 'type' => 'bool', 'label' => 'Show publisher call-to-action', 'default' => true],
        'home_publisher_cta_title' => ['group' => 'homepage', 'type' => 'string', 'label' => 'Publisher CTA title', 'default' => 'Sell your digital products', 'rules' => 'nullable|string|max:150'],
        'home_publisher_cta_text' => ['group' => 'homepage', 'type' => 'text', 'label' => 'Publisher CTA text', 'default' => 'Join as a publisher, upload your work and reach thousands of customers. Every product is reviewed before it goes live.', 'rules' => 'nullable|string|max:400'],

        // Notifications
        'notify_mail_enabled' => ['group' => 'notifications', 'type' => 'bool', 'label' => 'Send email notifications', 'default' => false, 'help' => 'Database (in-app) notifications are always on. Email requires MAIL_* settings in .env.'],

        // SEO
        'seo_default_title' => ['group' => 'seo', 'type' => 'string', 'label' => 'Default site title', 'default' => 'KN Bazar - Digital Product Marketplace', 'rules' => 'required|string|max:120'],
        'seo_title_separator' => ['group' => 'seo', 'type' => 'string', 'label' => 'Title separator', 'default' => ' | ', 'rules' => 'nullable|string|max:10'],
        'seo_default_description' => ['group' => 'seo', 'type' => 'text', 'label' => 'Default meta description', 'default' => 'Buy and download premium and free digital products: themes, plugins, templates, graphics and eBooks.', 'rules' => 'nullable|string|max:300'],
        'seo_default_keywords' => ['group' => 'seo', 'type' => 'string', 'label' => 'Default keywords', 'default' => 'digital products, themes, plugins, templates, ebooks', 'rules' => 'nullable|string|max:300'],
        'seo_default_og_image' => ['group' => 'seo', 'type' => 'image', 'label' => 'Default Open Graph image', 'default' => null, 'help' => '1200x630 recommended.'],
        'seo_home_title' => ['group' => 'seo', 'type' => 'string', 'label' => 'Homepage title', 'default' => '', 'rules' => 'nullable|string|max:120'],
        'seo_home_description' => ['group' => 'seo', 'type' => 'text', 'label' => 'Homepage description', 'default' => '', 'rules' => 'nullable|string|max:300'],
        'seo_home_keywords' => ['group' => 'seo', 'type' => 'string', 'label' => 'Homepage keywords', 'default' => '', 'rules' => 'nullable|string|max:300'],
        'seo_twitter_handle' => ['group' => 'seo', 'type' => 'string', 'label' => 'X / Twitter handle (e.g. @knbazar)', 'default' => '', 'rules' => 'nullable|string|max:50'],
        'seo_robots_index' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Allow search engines to index the site', 'default' => true],
        'seo_sitemap_enabled' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Enable XML sitemap', 'default' => true],
        'seo_sitemap_products' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Include products in sitemap', 'default' => true],
        'seo_sitemap_categories' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Include categories in sitemap', 'default' => true],
        'seo_sitemap_pages' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Include pages in sitemap', 'default' => true],
        'seo_sitemap_publishers' => ['group' => 'seo', 'type' => 'bool', 'label' => 'Include publisher profiles in sitemap', 'default' => true],
        'seo_google_verification' => ['group' => 'seo', 'type' => 'string', 'label' => 'Google site verification code', 'default' => '', 'rules' => 'nullable|string|max:150'],
        'seo_bing_verification' => ['group' => 'seo', 'type' => 'string', 'label' => 'Bing site verification code', 'default' => '', 'rules' => 'nullable|string|max:150'],
    ],
];
