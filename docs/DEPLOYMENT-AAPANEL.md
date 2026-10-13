# Deploying KN Bazar on aaPanel

Step-by-step guide for a fresh Linux server managed with **aaPanel**, using:

| | Value |
|---|---|
| Domain | `knbazaar.com` (and `www.knbazaar.com`) |
| Site directory | `/www/wwwroot/website` |
| Web root (running directory) | `/www/wwwroot/website/public` |
| Repository | `https://github.com/knsoftic/knbazar.git` (branch `main`) |
| PHP | 8.4 (8.3 also works); CLI binary `/www/server/php/84/bin/php` |
| Database | MySQL 8.0 (or MariaDB 10.6+) |

> Commands below are run as **root** over SSH (or aaPanel **Terminal**). If you install PHP 8.3 instead of 8.4, replace `84` with `83` in every path.

---

## 0. Before you start

1. **DNS:** at your domain registrar, create `A` records for `knbazaar.com` and `www.knbazaar.com` pointing to the server's IP. SSL in step 9 needs them.
2. **Firewall:** in aaPanel **Security**, make sure ports `80` and `443` are open.
3. Server: 1 vCPU / 2 GB RAM minimum, with enough disk for your product files (they are stored on the server under `storage/app/private`).

---

## 1. Install the software (aaPanel → App Store)

Install:

- **Nginx** 1.24+ (recommended; Apache also works, see the note in step 6)
- **MySQL 8.0** (or MariaDB 10.6+)
- **PHP-8.4**
- *(optional)* **phpMyAdmin**, **Supervisor** (only if you later use queues)

---

## 2. Configure PHP 8.4 (App Store → PHP-8.4 → Settings)

### 2.1 Install extensions (tab *Install extensions*)

Install **`fileinfo`** (required: upload validation fails without it), **`opcache`** (performance), **`exif`** (auto-rotates phone photos) and **`intl`**.
Already built into aaPanel's PHP: `gd` (with WebP), `mbstring`, `openssl`, `curl`, `pdo_mysql`, `zip`, `tokenizer`, `xml`, `ctype`.

### 2.2 Re-enable functions (tab *Disabled functions*)

aaPanel disables functions that Composer and Laravel need. **Remove these from the list:**

- `putenv`
- `proc_open`
- `proc_get_status`
- `symlink`

### 2.3 Upload limits and timeouts (tab *Configuration modify*)

```ini
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
date.timezone = Asia/Karachi
```

These must match the *Max product file size* you set later in **Admin → Settings → Uploads** (default 100 MB).

Click **Restart** for PHP-8.4.

### 2.4 Nginx upload size (App Store → Nginx → Settings → Configuration modify)

Inside the `http { ... }` block, change:

```nginx
client_max_body_size 110m;
```

Save, then **Reload** Nginx. Without this, large uploads fail with **413 Request Entity Too Large**.

---

## 3. Create the website and database (Website → Add site)

| Field | Value |
|---|---|
| Domain | `knbazaar.com` and `www.knbazaar.com` (one per line) |
| Root directory | `/www/wwwroot/website` |
| PHP version | PHP-84 |
| Database | MySQL, charset **utf8mb4** |
| Database name | `knbazar` |
| Database user / password | e.g. `knbazar` / *a strong generated password* |

Note the database name, user and password; you'll need them in step 5.

---

## 4. Upload the code from GitHub

aaPanel puts placeholder files in the new site folder. `.user.ini` is write-protected (`chattr +i`), so unlock it before cleaning the folder:

```bash
cd /www/wwwroot/website
chattr -i .user.ini 2>/dev/null || true
rm -rf .user.ini index.html 404.html .htaccess
ls -A    # must be empty now
```

Clone the repository into the (now empty) folder:

```bash
git clone https://github.com/knsoftic/knbazar.git .
```

> **Private repository?** Use a GitHub *Personal Access Token* (`https://<TOKEN>@github.com/knsoftic/knbazar.git`) or add a **Deploy key** (GitHub → repo → Settings → Deploy keys) and clone with `git@github.com:knsoftic/knbazar.git`.

### 4.1 Install Composer (once per server)

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | /www/server/php/84/bin/php
mv composer.phar /usr/local/bin/composer
```

### 4.2 Install PHP dependencies

```bash
cd /www/wwwroot/website
COMPOSER_ALLOW_SUPERUSER=1 /www/server/php/84/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

> Tip: to type `php` instead of the full path, run `alias php=/www/server/php/84/bin/php` (or add it to `~/.bashrc`). The rest of this guide assumes this alias.

```bash
alias php=/www/server/php/84/bin/php
```

---

## 5. Configure the environment (`.env`)

```bash
cd /www/wwwroot/website
cp .env.example .env
nano .env
```

Set at least:

```dotenv
APP_NAME="KN Bazar"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://knbazaar.com

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=knbazar
DB_USERNAME=knbazar
DB_PASSWORD=your-database-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

# Email (password reset, verification, notification emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=no-reply@knbazaar.com
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="no-reply@knbazaar.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Generate the application key:

```bash
php artisan key:generate --force
```

> Never commit `.env` and never set `APP_DEBUG=true` on the live site: it exposes configuration in error pages.

---

## 6. Database, admin account and storage

Create the tables:

```bash
php artisan migrate --force
```

Install the roles/permissions and the default pages (About, Contact, Terms, Privacy, Refund, Publisher Guidelines, How It Works):

```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=PageSeeder --force
```

Create **your** Super Admin account (you will be asked for a password):

```bash
php artisan marketplace:create-admin admin@knbazaar.com --name="KN Bazar Admin"
```

Link public storage (product images, logos):

```bash
php artisan storage:link
```

> If `storage:link` fails with *Call to undefined function symlink()*, either remove `symlink` from PHP's disabled functions (step 2.2) or create the link manually:
> `ln -s /www/wwwroot/website/storage/app/public /www/wwwroot/website/public/storage`

<details>
<summary><strong>Optional: install the demo data instead</strong> (testing/staging only)</summary>

```bash
php artisan db:seed --force
```

This adds demo categories, products, payment gateways, orders and the demo accounts from the README, all with the password `password`. **Delete or change those accounts before launch.** To start clean again: `php artisan migrate:fresh --force`, then repeat step 6.
</details>

---

## 7. Permissions

aaPanel runs PHP as the `www` user:

```bash
cd /www/wwwroot/website
chown -R www:www /www/wwwroot/website
find . -type d -not -path "./vendor/*" -exec chmod 755 {} \;
find . -type f -not -path "./vendor/*" -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 640 .env
chmod +x scripts/aapanel-deploy.sh
```

---

## 8. Site settings in aaPanel (Website → knbazaar.com → Conf)

### 8.1 Site directory

- **Running directory:** `/public` → **Save**.
- **Anti-XSS attack (open_basedir):** can stay **on**, because the restriction covers `/www/wwwroot/website/`. If you see `open_basedir restriction in effect` errors, turn it off.

### 8.2 URL rewrite

Select the **laravel5** template, or paste:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Save.

### 8.3 Config file (extra hardening, optional)

Add inside the `server { ... }` block, above the `#PHP-INFO-START` line:

```nginx
# Block hidden files (.env, .git) - the web root is /public anyway
location ~ /\.(?!well-known).* { deny all; }

# Long cache for bundled assets
location ^~ /vendor/ { expires 30d; access_log off; }
location ^~ /assets/ { expires 7d; access_log off; }
```

> **Apache instead of Nginx?** Set the running directory to `/public`; the included `public/.htaccess` handles rewrites. No extra rewrite rules are needed.

---

## 9. SSL (Website → knbazaar.com → SSL)

1. **Let's Encrypt** → select both `knbazaar.com` and `www.knbazaar.com` → **Apply**.
2. Turn on **Force HTTPS**.
3. Keep `SESSION_SECURE_COOKIE=true` and `APP_URL=https://knbazaar.com` in `.env`.

---

## 10. Optimise and go live

```bash
cd /www/wwwroot/website
php artisan optimize
```

Open **https://knbazaar.com**, sign in at **https://knbazaar.com/login** with the Super Admin you created, then go to **/admin**.

### First-run checklist in the admin panel

1. **Settings → General:** website name, logo, favicon, contact email/phone/address, **currency** (PKR) and timezone.
2. **Settings → WhatsApp:** enable it and set the country code (`92`), number and message template.
3. **Payment Gateways:** add your real JazzCash / EasyPaisa / bank / USDT accounts (account title, number, IBAN, wallet...) and instructions.
4. **Categories:** create your categories (name, icon, image, SEO).
5. **Settings → Publishers:** choose whether new publishers need approval.
6. **Settings → Uploads:** allowed file types and size limits (keep them in line with step 2.3).
7. **SEO:** default title/description, OG image, Google verification code. Then submit `https://knbazaar.com/sitemap.xml` in Google Search Console.
8. **Pages:** review and edit Terms, Privacy and Refund policy.
9. **Admins:** create staff accounts with only the permissions they need.

---

## 11. Updating the site after new commits

Push changes to GitHub, then on the server:

```bash
bash /www/wwwroot/website/scripts/aapanel-deploy.sh
```

The script puts the site in maintenance mode, pulls `main`, installs dependencies, runs migrations, syncs roles/permissions and default pages, rebuilds caches, fixes permissions and brings the site back up.

> The script does `git reset --hard origin/main`, so **never edit code directly on the server**. `.env`, uploaded files and logs are not tracked by git and are kept.

Manual equivalent:

```bash
cd /www/wwwroot/website
php artisan down
git pull origin main
COMPOSER_ALLOW_SUPERUSER=1 php /usr/local/bin/composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
chown -R www:www .
php artisan up
```

---

## 12. Backups (Cron → Add cron task)

Add two daily tasks:

| Type | Target | Keep |
|---|---|---|
| **Backup Database** | `knbazar` | 7-14 copies |
| **Backup Site** | `knbazaar.com` | 7 copies |

**Important:** customers' purchased files and payment screenshots live in `storage/app/private`, and product images in `storage/app/public`. They are not in git, so the site backup is the only copy. Store backups off-server (aaPanel supports S3, FTP, Google Drive...).

---

## 13. Optional: cron and queue

**Scheduler:** not required today. If scheduled jobs are added later, create a *Shell Script* cron task that runs **every minute**:

```bash
cd /www/wwwroot/website && /www/server/php/84/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker:** only needed if you set `QUEUE_CONNECTION=database`. In the **Supervisor** app, add a daemon:

| Field | Value |
|---|---|
| Name | `knbazar-queue` |
| Run user | `www` |
| Run dir | `/www/wwwroot/website` |
| Start command | `/www/server/php/84/bin/php artisan queue:work --tries=3 --max-time=3600` |
| Processes | 1 |

After every deploy, run `php artisan queue:restart`.

---

## 14. Security checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS forced, `SESSION_SECURE_COOKIE=true`
- [ ] Demo accounts removed (if demo data was ever seeded)
- [ ] `.env` is `640` and owned by `www`
- [ ] aaPanel **security entrance** enabled and panel port not left at its default; panel login uses a strong password
- [ ] phpMyAdmin not publicly reachable (or protected)
- [ ] SSH key login; password login disabled if possible
- [ ] Daily database and site backups stored off-server
- [ ] `https://knbazaar.com/storage/app/private/...` and `/.env` return 404/403 (they should: the web root is `/public`)

---

## 15. Troubleshooting

| Symptom | Fix |
|---|---|
| **500 Server Error** | Read `storage/logs/laravel.log`. Most common causes: permissions (step 7), missing `.env`/`APP_KEY`, wrong DB credentials. |
| Blank page / *Please provide a valid cache path* | `chmod -R 775 storage bootstrap/cache` and `chown -R www:www .` |
| `open_basedir restriction in effect` | Turn off **Anti-XSS attack** in Site directory (step 8.1). |
| `Call to undefined function proc_open()` / `putenv()` / `symlink()` | Remove them from PHP disabled functions (step 2.2) and restart PHP. |
| `The file failed to upload` / uploads silently fail | Increase PHP limits (step 2.3) **and** Nginx `client_max_body_size` (step 2.4). |
| **413 Request Entity Too Large** | Nginx `client_max_body_size` (step 2.4). |
| Uploads rejected: *"must be a file of type..."* | Install the PHP `fileinfo` extension (step 2.1); check **Admin → Settings → Uploads**. |
| Images don't show (404 on `/storage/...`) | Run `php artisan storage:link` (or create the symlink manually, step 6). |
| **404** on every page except the homepage | URL rewrite missing (step 8.2) or running directory not set to `/public` (step 8.1). |
| **419 Page Expired** after login/forms | Mixed HTTP/HTTPS: force HTTPS, set `APP_URL=https://...`; clear browser cookies; run `php artisan optimize:clear`. |
| Settings changes don't apply | `php artisan optimize:clear && php artisan optimize` |
| Password reset / emails not arriving | Check `MAIL_*` in `.env`; many hosts block port 25, so use 587 or 465 with your SMTP provider. |
| `SQLSTATE[HY000] [1045] Access denied` | DB user/password in `.env` don't match the database created in step 3. |
| `SQLSTATE[42000] ... Specified key was too long` | You're on very old MySQL (< 5.7.7); use MySQL 8.0 or MariaDB 10.6+. |

Application logs: `storage/logs/laravel.log` and `storage/logs/marketplace-YYYY-MM-DD.log` (payments, approvals, downloads, security events). Both are also viewable in **Admin → System Logs**.
