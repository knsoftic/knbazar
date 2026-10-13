<?php

/*
|--------------------------------------------------------------------------
| Marketplace configuration
|--------------------------------------------------------------------------
|
| Static, code-level configuration for the marketplace. Values that admins
| should be able to change at runtime live in the settings table instead
| (see config/settings.php for their definitions and defaults).
|
*/

return [

    // Product types offered in product forms. Add new types here.
    'product_types' => [
        'theme' => 'Theme',
        'plugin' => 'Plugin',
        'script' => 'Script',
        'template' => 'Template',
        'graphics' => 'Graphics',
        'ebook' => 'eBook',
        'course' => 'Course',
        'audio' => 'Audio',
        'video' => 'Video',
        'font' => 'Font',
        'software' => 'Software',
        'other' => 'Other',
    ],

    // Supported currencies (code => symbol).
    'currencies' => [
        'PKR' => 'Rs',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'AED' => 'AED',
        'SAR' => 'SAR',
        'BDT' => '৳',
        'USDT' => 'USDT',
    ],

    'order_number_prefix' => 'ORD',

    'pagination' => [
        'products' => 12,
        'admin' => 20,
        'account' => 15,
    ],

    'image' => [
        'max_width' => 1600,
        'thumb_width' => 600,
        'thumb_height' => 450,
        'gallery_thumb_width' => 300,
        'gallery_thumb_height' => 225,
        'avatar_size' => 300,
        'quality' => 82,
    ],

    // Extensions that may never be uploaded as product files, regardless of
    // what an administrator configures in the upload settings.
    'blocked_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht',
        'cgi', 'pl', 'asp', 'aspx', 'jsp', 'jspx', 'shtml', 'htaccess', 'htpasswd',
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'pif', 'vbs', 'vbe', 'wsf', 'ps1',
        'sh', 'bash', 'dll', 'so', 'jar', 'html', 'htm', 'xhtml', 'svg', 'svgz',
    ],

    // MIME types that are rejected even when the extension looks harmless.
    'blocked_mimes' => [
        'text/x-php', 'application/x-php', 'application/x-httpd-php', 'text/php',
        'application/x-dosexec', 'application/x-msdownload', 'application/x-msdos-program',
        'application/x-executable', 'application/x-sharedlib', 'application/x-sh',
        'text/x-shellscript', 'text/html', 'application/xhtml+xml', 'image/svg+xml',
        'application/java-archive', 'application/x-ms-installer',
    ],

    // Usernames that would collide with routes under /publisher.
    'reserved_usernames' => [
        'admin', 'administrator', 'root', 'system', 'support', 'publisher', 'publishers',
        'products', 'product', 'orders', 'order', 'profile', 'dashboard', 'register',
        'login', 'logout', 'settings', 'account', 'api', 'download', 'downloads',
        'cart', 'checkout', 'search', 'category', 'categories', 'page', 'pages',
    ],

    'search' => [
        // Use MySQL/MariaDB FULLTEXT matching on name/descriptions when available.
        'fulltext' => env('MARKETPLACE_FULLTEXT_SEARCH', true),
        'min_term_length' => 2,
    ],

    'cache' => [
        'home_ttl' => 300,
        'nav_ttl' => 600,
        'sitemap_ttl' => 3600,
    ],
];
