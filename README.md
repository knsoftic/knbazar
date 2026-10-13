# KN Bazar - Digital Product Marketplace

A production-ready marketplace for **free and paid digital products** (themes, plugins, scripts, templates, graphics, eBooks, fonts...) built with **Laravel 13**, Blade, Bootstrap 5 and vanilla JavaScript. No frontend framework or build step is required.

- Publishers register, upload products and submit them for review. **Nothing goes live without admin approval.**
- Customers browse, search and filter, download free products, and buy paid products through **manual payment gateways** (JazzCash, EasyPaisa, bank transfer, USDT...) or **Order via WhatsApp**.
- Admins verify payments, approve products, and manage the entire site from a role- and permission-based admin panel.
- Files are stored privately and only delivered through **authorised, tracked download links**.
- SEO is built in: clean URLs, per-product meta/OG/Twitter tags, JSON-LD Product schema, XML sitemap and dynamic robots.txt.

---

## Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Demo accounts](#demo-accounts)
5. [Configuration](#configuration)
6. [Development](#development)
7. [Production deployment](#production-deployment)
8. [Architecture](#architecture)
9. [Workflows](#workflows)
10. [Security](#security)
11. [Extending the marketplace](#extending-the-marketplace)

---

## Features

| Area | Highlights |
|---|---|
| **Roles** | Super Admin, Admin (configurable permissions), Publisher, Customer. Middleware + Gates + Policies. |
| **Products** | Free/paid, regular & sale price with automatic discount %, main image + gallery, private downloadable file, version, demo/docs links, features & requirements, keywords, full SEO fields, featured flag, per-product WhatsApp toggle, view/download/sales counters. |
| **Workflow** | Draft → Pending Review → Approved/Published or Rejected (with reason) → edit → resubmit. Full status history. Editing a live product sends it back to review. |
| **Catalog** | Home sections (featured, latest, free, paid, popular, categories, publisher CTA), listing with filters (free/paid, price range, category, type, featured) and sorting (latest, popular, downloads, price), search across name, descriptions, keywords, category and publisher, category pages, publisher storefronts. |
| **Cart & checkout** | Session cart, Buy Now, AJAX add-to-cart, duplicate-purchase prevention, optional quantities, authenticated checkout. |
| **Orders** | `ORD-2026-000001` numbers, statuses (Pending, Awaiting Payment, Payment Submitted, Processing, Completed, Cancelled, Refunded), history timeline, internal admin notes, cancel/complete/refund. |
| **Payments** | Unlimited manual gateways with flexible account fields (label/value), logo, instructions, currency, screenshot requirement. Customers submit transaction ID, sender details, amount and screenshot. Admin approve/reject (reason required). Duplicate transaction ID detection. |
| **Downloads** | Private storage, authorisation checks, per-purchase limits and expiry (configurable), tracking of user, product, order, IP, user agent and time. Free downloads can be disabled or require login. |
| **WhatsApp** | Global on/off, country code, number and message template with placeholders. Auto-generated `wa.me` links. |
| **Dashboards** | Admin dashboard with 12 stat cards and 5 charts. Publisher dashboard with stats, sales and download charts. Customer account with orders, downloads, purchases, payment history and notifications. |
| **Reports** | Sales, orders, products, downloads, publishers, payments and revenue, with Today / Yesterday / This week / This month / Last month / Last 30 days / custom ranges and CSV export. |
| **Content** | Editable pages (About, Contact, Terms, Privacy, Refund, Publisher Guidelines, How It Works), homepage banners, contact form with inbox. |
| **Settings** | Site identity, logo/favicon, contact, currency, timezone, social links, registration, publisher approval, checkout, downloads, WhatsApp, upload limits, homepage, notifications and SEO defaults, all editable in the admin panel. |
| **Notifications** | In-app (database) notifications with a top-bar bell; optional email. |
| **Logs** | Activity log of admin and security events plus a log-file viewer. |
| **UX** | Responsive on desktop, tablet and mobile: off-canvas admin sidebar, collapsible desktop sidebar, tables that stack into cards on phones, mobile-first checkout and product pages. Custom 403/404/419/422/429/500/503 pages. |

---

## Requirements

| | Version |
|---|---|
| PHP | **8.3 or newer** (developed and tested on PHP 8.4.26) |
| Laravel | **13.x** (13.35) |
| Database | **MySQL 8.0+** or **MariaDB 10.4+** (tested on MariaDB 10.4.32) |
| Composer | 2.x |
| Web server | Apache (mod_rewrite) or Nginx |

Required PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `ctype`, `xml`, `curl`, `zip`, **`gd` with WebP support** (image processing). Optional: `exif` (auto-rotates phone photos), `intl`.

> **XAMPP note:** XAMPP versions that ship PHP 8.2 cannot run Laravel 13. Either install an XAMPP build with PHP 8.3+, or use a separate PHP 8.3+ binary for Composer/Artisan (this project was developed with a portable PHP 8.4 in `C:\php84` alongside XAMPP's MariaDB). Use `php artisan serve` or point a PHP 8.3+ web server at `public/`.

No Node.js build is needed: Bootstrap 5.3.8, Bootstrap Icons 1.13.2 and Chart.js 4.5.1 are bundled in `public/vendor/`.

---

## Installation

```bash
composer install
```

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

Create an empty database (e.g. `knbazar`, `utf8mb4_unicode_ci`), then set `DB_*` in `.env`.

```bash
php artisan migrate --seed
```

```bash
php artisan storage:link
```

```bash
php artisan optimize
```

Start the development server and open http://127.0.0.1:8000:

```bash
php artisan serve
```

> In non-interactive shells (CI, scripts) add `--no-interaction` (and `--force` in production) to Artisan commands, e.g. `php artisan migrate --seed --force --no-interaction`.

**Seeding:** `php artisan migrate --seed` installs the roles/permissions *and* demo content (accounts, 7 categories, 17 products with generated artwork and downloadable demo ZIP files, 4 payment gateways, 7 pages, a banner and sample orders). For a clean production install seed only the reference data:

```bash
php artisan db:seed --class=RolePermissionSeeder --force
```

```bash
php artisan db:seed --class=PageSeeder --force
```

`PageSeeder` adds the default About, Contact, Terms, Privacy, Refund, Publisher Guidelines and How It Works pages and never overwrites pages you have edited. Then create your first Super Admin (you'll be prompted for a password):

```bash
php artisan marketplace:create-admin you@example.com --name="Your Name"
```

---

## Demo accounts

All demo accounts use the password **`password`**. Change or delete them before going live.

| Role | Email | Notes |
|---|---|---|
| Super Admin | `superadmin@knbazar.test` | Full access |
| Admin | `admin@knbazar.test` | Products, approvals, categories, orders, payments, downloads, reports, messages |
| Publisher | `publisher@knbazar.test` | Approved publisher "PixelCraft Studio" with published, pending, rejected and draft products |
| Publisher | `publisher2@knbazar.test` | Approved publisher "CodeNest" |
| Publisher (pending) | `pending.publisher@knbazar.test` | Awaiting approval: restricted dashboard |
| Customer | `customer@knbazar.test` | Has a completed order (downloads available), a payment under review and an unpaid order |

Admin panel: `/admin` · Publisher dashboard: `/publisher` · Customer account: `/account`

---

## Configuration

Nearly everything is configured in **Admin → Settings** (and **Admin → SEO**). Settings are stored in the `settings` table and cached; their definitions, defaults and validation live in `config/settings.php`, so adding a new setting is a one-line change.

### Environment (`.env`)

| Key | Purpose |
|---|---|
| `APP_URL` | Public URL (used in emails/CLI). Page links are generated from the current request host. |
| `APP_ENV` / `APP_DEBUG` | Use `production` / `false` on live servers. |
| `DB_*` | Database connection. |
| `SESSION_DRIVER`, `SESSION_SECURE_COOKIE` | Sessions are stored in the database. Set `SESSION_SECURE_COOKIE=true` with HTTPS. |
| `CACHE_STORE` | `database` by default; `redis` or `file` also work. |
| `QUEUE_CONNECTION` | `sync` by default (no worker needed). |
| `MAIL_*` | Optional: password resets, email verification and notification emails. `log` writes them to `storage/logs/laravel.log`. |
| `FILESYSTEM_DISK` | Keep `local` (private product files). |
| `MARKETPLACE_FULLTEXT_SEARCH` | Use MySQL FULLTEXT relevance ordering for search results. |

### Payment gateways

**Admin → Payment Gateways → Add gateway**

- **Name, logo, short description, currency, sort order, active.**
- **Account fields:** add any number of label/value rows such as *Account Title*, *Account Number*, *IBAN*, *Bank Name*, *Wallet Address*, *Network*. Nothing is hard-coded.
- **Instructions:** shown to the customer after checkout.
- **Require payment screenshot:** forces customers to upload proof.

Customers choose a gateway at checkout, see the instructions (with copy buttons) on the payment page, and submit the transaction ID, sender name/account, amount, screenshot and notes. Verify them under **Admin → Payments**. Approving marks the order Paid/Completed and unlocks downloads; rejecting requires a reason that the customer sees, and they can resubmit.

### WhatsApp ordering

**Admin → Settings → WhatsApp**

| Setting | Example |
|---|---|
| Enable "Order via WhatsApp" globally | on |
| Country code | `92` |
| WhatsApp number | `3001234567` (a leading `0` or the country code is handled) |
| Button text | `Order via WhatsApp` |
| Message template | `Hello, I want to purchase this product:\nProduct: {product_name}\nProduct ID: {product_id}\nPrice: {price}\nProduct URL: {product_url}` |

Placeholders: `{product_name}`, `{product_id}`, `{price}`, `{regular_price}`, `{product_url}`, `{category}`, `{publisher}`, `{site_name}`. The button appears on **paid, published** products when enabled globally *and* on the product (each product has its own toggle).

### SEO

**Admin → SEO:** default title and separator, default description/keywords, default Open Graph image, homepage title/description/keywords, X/Twitter handle, Google/Bing verification codes, search-engine indexing (robots) and sitemap content (products, categories, pages, publishers).

Per item:
- **Products:** SEO title, description, keywords, focus keyword, canonical URL, OG title/description/image, slug, and keywords that drive search. Empty fields fall back to the product content.
- **Categories and pages:** SEO title, description and keywords.

URLs: `/product/{slug}`, `/category/{slug}`, `/publisher/{username}`, `/page/{slug}`, `/search?q=`. Changing a product slug keeps a **301 redirect** from the old URL. `/sitemap.xml` and `/robots.txt` are generated dynamically.

### File uploads

**Admin → Settings → Uploads:** allowed image formats (`jpg,jpeg,png,webp`), max image size (KB), max gallery images, allowed product file formats and max product file size (MB).

PHP must allow the same size. Example `php.ini` for 100 MB product files:

```ini
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
```

Executable and script formats (`php`, `phtml`, `phar`, `cgi`, `asp`, `jsp`, `exe`, `msi`, `bat`, `sh`, `ps1`, `jar`, `html`, `svg`, `.htaccess`...) are **always blocked** regardless of settings (see `blocked_extensions` / `blocked_mimes` in `config/marketplace.php`).

### Other settings

- **Registration:** customer registration on/off, require email verification (needs working mail).
- **Publishers:** publisher registration on/off, **require admin approval** (pending publishers can log in and edit their profile but cannot submit products), max products pending review.
- **Downloads:** enable free downloads, require login for free downloads, download limit per purchase, access expiry in days.
- **Cart & checkout:** require address, terms page, allow quantities, customers can cancel unpaid orders.
- **Homepage:** hero text, products per section, show/hide each section, publisher CTA. Banners live under **Admin → Homepage Banners**.

---

## Development

```bash
php artisan serve
```

```bash
php artisan test
```

```bash
vendor/bin/pint
```

The test suite (77 tests) covers registration, publisher registration/approval, product submission, approval, rejection and resubmission, product visibility, free downloads, paid orders, payment submission/approval/rejection, secure downloads and limits, role and permission authorisation, SEO output, WhatsApp links, pricing and slugs, plus a **smoke test that renders every page for every role** using the demo data. Tests run on in-memory SQLite.

---

## Production deployment

> **aaPanel users:** follow the step-by-step guide in [docs/DEPLOYMENT-AAPANEL.md](docs/DEPLOYMENT-AAPANEL.md) (domain `knbazaar.com`, path `/www/wwwroot/website`). Later updates are one command: `bash scripts/aapanel-deploy.sh`.

Generic steps for any server:

1. Upload the project (without `node_modules`), and point the **web root to `public/`**. On shared hosting where that's impossible, place the contents of `public/` in `public_html` and adjust the paths in `index.php`.
2. Install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Configure `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, database, mail, `SESSION_SECURE_COOKIE=true`.
4. Migrate, link storage and cache:
   ```bash
   php artisan migrate --force
   ```
   ```bash
   php artisan storage:link
   ```
   ```bash
   php artisan optimize
   ```
5. Permissions: the web server needs write access to `storage/` and `bootstrap/cache/`.
6. Serve over HTTPS. `storage/app/private` (product files and payment screenshots) must never be web-accessible; it lives outside `public/` by default.
7. After every deploy run `php artisan optimize` again (or `php artisan optimize:clear` when debugging).

**Apache:** the included `public/.htaccess` handles rewrites. **Nginx:**

```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.4-fpm.sock; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
location ~ /\.(?!well-known).* { deny all; }
```

### Queue (optional)

Notifications are sent synchronously with `QUEUE_CONNECTION=sync`, and delivery errors never break the action that triggered them. For high traffic or slow SMTP, switch to `QUEUE_CONNECTION=database` and run a worker under Supervisor:

```bash
php artisan queue:work --tries=3 --max-time=3600
```

### Cron (optional)

No scheduled tasks are required today. If you add scheduled jobs later, add the standard Laravel cron entry:

```
* * * * * cd /path/to/knbazar && php artisan schedule:run >> /dev/null 2>&1
```

---

## Architecture

```
app/
├── Enums/                  ProductStatus, ApprovalStatus, OrderStatus, PaymentStatus, PaymentRecordStatus, PublisherStatus, UserStatus, RoleName
├── Exceptions/             DownloadDeniedException
├── Http/
│   ├── Controllers/        Public storefront, Auth/, Account/ (customer), Publisher/, Admin/
│   ├── Middleware/         role, verified.optional, publisher.approved, active-account, security headers
│   └── Requests/           Form Requests for every form (Auth/, Account/, Admin/, Publisher/, product, checkout, payment...)
├── Models/                 23 Eloquent models with relationships, casts and scopes
├── Notifications/          ProductSubmitted/Approved/Rejected, PaymentSubmitted/Approved/Rejected, OrderCompleted, publisher events
├── Payments/               PaymentGatewayDriver contract, ManualGateway driver, PaymentGatewayManager
├── Policies/               ProductPolicy (publisher ownership), OrderPolicy (customer ownership)
├── Rules/                  SafeProductFile (extension allow/block list + MIME sniffing)
├── Services/               Business logic (see below)
└── Support/                SeoData, DateRange, UploadRules, UsernameRule, Countries
config/
├── marketplace.php         product types, currencies, blocked file types, reserved usernames, image sizes
├── permissions.php         assignable admin permissions (registered as Gates)
└── settings.php            admin-editable settings: groups, types, defaults, validation
```

**Services**

| Service | Responsibility |
|---|---|
| `ProductService` | Create/update products, slugs + slug history, keywords, image processing, private files |
| `ProductApprovalService` | Submit, approve, reject, publish, unpublish, feature, with history, notifications and logs |
| `ProductSearchService` | Catalog query: search, filters and sorting |
| `CartService` | Session cart with database-priced lines |
| `OrderService` | Order creation and numbering, status transitions, download permission grant/revoke |
| `PaymentService` | Payment submission, approval and rejection |
| `DownloadService` | Download authorisation, tracking and streaming |
| `WhatsAppOrderService` | WhatsApp number normalisation, message template and link |
| `SeoService` | Meta, Open Graph, Twitter and JSON-LD for every page type |
| `ReportService` | Dashboard statistics, charts and reports |
| `SettingService` | Cached runtime settings |
| `ImageService` / `FileUploadService` | GD re-encoding to WebP, thumbnails, secure filenames, private storage |
| `ActivityLogger` / `NotificationService` | Activity and security logging, failure-tolerant notifications |

**Database** (normalised, with foreign keys, indexes, unique constraints and soft deletes where appropriate): `users`, `roles`, `permissions`, `permission_user`, `publisher_profiles`, `categories`, `products`, `product_images`, `keywords`, `product_keywords`, `product_slug_redirects`, `product_status_histories`, `payment_gateways`, `orders`, `order_items`, `order_status_histories`, `payments`, `payment_status_histories`, `download_permissions`, `downloads`, `pages`, `settings`, `contact_messages`, `banners`, `activity_logs`, `notifications`, plus Laravel's `sessions`, `cache` and `jobs`.

---

## Workflows

**Product approval**

```
Publisher creates product → Save as Draft → Submit for Review → Pending
Admin reviews → Approve (+ publish now or later) → Published
             → Reject (reason required) → Publisher edits → Resubmit → Pending
```

Only products that are approved *and* published, in an active category, owned by an active account appear publicly. Owners and reviewers get a non-indexed **preview** of non-public products.

**Paid purchase**

```
Buy Now / Cart → Checkout (details + gateway) → Order "Awaiting Payment"
→ Payment instructions → Customer submits transaction details → "Payment Submitted" / "Under Review"
→ Admin approves → Order "Completed" (paid) → Download permission granted
             → Admin rejects (reason) → Customer sees reason → resubmits
```

**WhatsApp order:** the product page builds the message from the template and opens `wa.me`. The customer contacts the seller, who can mark a manual order completed from the admin.

**Free download:** `/download/{slug}` checks that free downloads are enabled, the product is public and (if configured) the user is logged in, then streams the file and records the download.

---

## Security

- CSRF protection on every form; Blade escaping everywhere; product descriptions are Markdown with raw HTML **escaped** and unsafe links removed. Static pages (staff-authored, `manage_pages` permission) allow HTML.
- Mass-assignment protection: workflow columns (status, approval, owner, file paths, counters, featured) are never fillable and only change through services.
- Validation through Form Requests for every form; prices are always read from the database.
- Role middleware, permission Gates (Super Admin bypass) and Policies for ownership.
- Uploads: extension allow-list + hard block-list, content-sniffed MIME checks, double-extension rejection, size limits, random filenames, images re-encoded with GD (strips embedded payloads and metadata).
- Product files and payment screenshots on the **private** disk (`storage/app/private`, direct serving disabled); downloads only through authorised controllers with `Content-Disposition: attachment` and `nosniff`.
- Rate limiting on login (per email + IP), registration, contact form, checkout, payment submission, cart, uploads and downloads.
- Suspended accounts are signed out on their next request; the last Super Admin cannot be demoted or deleted.
- Security headers: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS over HTTPS.
- Cache stores plain arrays only (Laravel 13 refuses to unserialize objects from cache).
- Passwords, tokens and payment details are never written to logs; CSV exports are protected against formula injection.

---

## Extending the marketplace

| Feature | Where to start |
|---|---|
| Automated payment gateways (Stripe, PayPal, JazzCash API...) | Implement `App\Payments\Contracts\PaymentGatewayDriver`, register it in `PaymentGatewayManager::$drivers`; store API keys in the encrypted `payment_gateways.config` column. |
| Coupons | Add a `coupons` table and apply discounts in `CartService::totals()` / `OrderService::createFromCart()` (orders already store `discount_total`). |
| Reviews & ratings | New `reviews` table (product_id, user_id, rating); the product page has a reserved ratings area. Purchases are verifiable through `download_permissions`. |
| Wishlist | `wishlists` pivot (user_id, product_id) plus a button on product cards. |
| Publisher commissions / payouts | `order_items` already records `publisher_id` and amounts per item; add a commission rate to `publisher_profiles` and a payouts table. |
| Licences / subscriptions | Extend `download_permissions` (limits, expiry and revocation already exist) or add a `licenses` table issued in `OrderService::grantDownloadPermissions()`. |
| API / mobile app | Business logic lives in services; add `routes/api.php` with Sanctum and reuse the services and enums. |
| Multi-language | All UI strings are in Blade templates, ready for `__()` and `lang/` files. |
| New product types or currencies | `config/marketplace.php`. |
| New settings | Add an entry to `config/settings.php`; the admin form and validation are generated automatically. |
| New admin permissions | Add to `config/permissions.php` and run `php artisan db:seed --class=RolePermissionSeeder`. |
