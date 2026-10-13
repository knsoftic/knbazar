<?php

/*
|--------------------------------------------------------------------------
| Admin permissions
|--------------------------------------------------------------------------
|
| Permissions that can be assigned to Admin accounts. Super Admins always
| have every permission. Each slug is registered as a Gate ability, so it
| can be used with `can:` middleware, @can directives and $user->can().
|
*/

return [
    'manage_users' => ['label' => 'Manage users & customers', 'group' => 'Users'],
    'manage_publishers' => ['label' => 'Manage publishers', 'group' => 'Users'],
    'manage_products' => ['label' => 'Manage products', 'group' => 'Catalog'],
    'approve_products' => ['label' => 'Approve / reject products', 'group' => 'Catalog'],
    'manage_categories' => ['label' => 'Manage categories', 'group' => 'Catalog'],
    'manage_orders' => ['label' => 'Manage orders', 'group' => 'Sales'],
    'manage_payments' => ['label' => 'Verify payments', 'group' => 'Sales'],
    'manage_payment_gateways' => ['label' => 'Manage payment gateways', 'group' => 'Sales'],
    'manage_downloads' => ['label' => 'View download history', 'group' => 'Sales'],
    'view_reports' => ['label' => 'View reports', 'group' => 'Sales'],
    'manage_pages' => ['label' => 'Manage pages', 'group' => 'Content'],
    'manage_homepage' => ['label' => 'Manage homepage & banners', 'group' => 'Content'],
    'manage_messages' => ['label' => 'Manage contact messages', 'group' => 'Content'],
    'manage_seo' => ['label' => 'Manage SEO settings', 'group' => 'System'],
    'manage_settings' => ['label' => 'Manage website settings', 'group' => 'System'],
    'view_logs' => ['label' => 'View system logs', 'group' => 'System'],
];
