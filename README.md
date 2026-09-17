# RestAssured Restaurant POS

Laravel 13 restaurant POS for cafes, restaurants, and takeaway counters. PHP 8.3, Bootstrap 5, and AJAX on the POS screen.

There is no Laravel **13.13** skeleton on Packagist. This app uses Laravel skeleton **13.10.1** with `laravel/framework` **^13.17** (installed 13.31) on PHP **8.3.33**.

## Demo logins

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@pos.test | password |
| Cashier | cashier@pos.test | password |

Admin manages catalog, tax, settings, dashboard, and reports. Cashier can open POS, complete orders, and print receipts.

## Setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://localhost:8000

### MySQL

Create a database, then in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_pos
DB_USERNAME=root
DB_PASSWORD=
```

Reference SQL is in `database/schema.sql`. Prefer migrations over running that file by hand.

Windows PHP used for this build: `C:\php83\php.exe`
