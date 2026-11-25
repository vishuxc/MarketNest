# MarketNest

MarketNest is a local marketplace for products and services. Users can register, sign in, publish listings with photos, and manage their own listings. Published listings appear on the homepage and have public detail pages.

## Screenshots

- [Desktop homepage](docs/screenshots/marketnest-desktop-home.png)
- [Desktop marketplace and filters](docs/screenshots/marketnest-desktop-browse.png)
- [Mobile listing gallery](docs/screenshots/marketnest-mobile-listing.png)

## Local setup on Windows

Use PHP 8.4.1 or later with PDO MySQL, Composer, and Node.js 20.19+ or 22.12+. The Composer lock file includes dependencies that require PHP 8.4.1.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd ci
npm.cmd run build
```

Run the environment copy and key generation only when creating a new local configuration. Open http://localhost:8000 and reduce the browser width to check the responsive header, hero, and footer. For live CSS and JavaScript updates, run `npm.cmd run dev` in a second terminal.

The environment template targets a MySQL database named `marketnest` at `127.0.0.1:3306`. Replace the example `DB_USERNAME` and set `DB_PASSWORD` in your local `.env` before running database migrations.

## Database and authentication

Create the `marketnest` schema and give the local application user access to it. Save those connection settings in the ignored `.env` file, then run the following commands with the intended schema selected.

```powershell
php artisan migrate:status
php artisan migrate --seed
php artisan storage:link
php artisan migrate:status
php artisan serve
```

On a fresh schema the first status command reports that the migration table is missing. The seeders add seven categories and 22 subcategories, plus India with four sample states and territories, six cities, and 15 areas. The locations are sample data for the assignment. Seeder runs can be repeated without duplicating records.

If MySQL reports error 1045, check the application account and its grants in MySQL Workbench. `using password: NO` means the connection attempt sent no password. Enter the correct credentials in `.env`, run `php artisan config:clear`, restart the local server if it is running, and retry `php artisan migrate:status`.

Set `APP_ENV=production` and `APP_DEBUG=false` in production. When the site uses HTTPS, set `SESSION_SECURE_COOKIE=true`. Connection failures return a generic HTTP 503 response to visitors, with diagnostics available through server-side logging.

Visit `/register` to create an account. Check that the login page shows the registration success message, then sign in with the new credentials. Verify that the homepage shows the account name and that logout restores guest navigation. Try confirming a different password at registration and entering an incorrect password at login to check the field messages. At mobile width, check that navigation wraps and that the password visibility toggle works using the keyboard.

## Listing management

Run `php artisan storage:link` once to make the public image disk accessible. Sign in, select **Sell**, and publish a product or service. Check that the listing appears on the homepage, then use **My Listings** to edit it, retain or replace photos, and confirm deletion.

The application accepts up to five JPG, PNG, or WebP photos of 5 MB each. On Windows, PHP may default to 2 MB per file. To run locally with the full upload limit and without changing global PHP settings, start the server from the project root with these commands.

```powershell
Push-Location public
try {
    php -d upload_max_filesize=5M -d post_max_size=32M -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
} finally {
    Pop-Location
}
```

Open `http://127.0.0.1:8000`. For consistent local image URLs, set `APP_URL` in `.env` to the same origin. When deploying, set equivalent upload and request body limits on the PHP and web server configuration.

## Browse listings

Open `/listings` as a guest and use the filter panel. Choose a category and city and apply the filters to open a combined URL such as `/city/chennai/electronics`. Clear Filters keeps the city and category in the current route. Use Browse in the navigation to return to the full marketplace.

From the homepage, follow a category or popular city link. Open any listing to check its breadcrumbs, category link, city link, and combined browse link. The dropdown filters use database IDs in the query string. The `country` and `state` query filters also accept database IDs.

Search from the header or homepage. The search checks listing titles, descriptions, category names, and subcategory names. Use the sidebar to combine search with an INR price range, location and type, then select a sort order. Check that **Clear search** preserves the rest of the filters.

### Optional demo listings

After `php artisan migrate --seed`, ensure `php artisan storage:link` has been run so the demo photos can be served. The optional command below adds 18 products and 6 services across six Indian cities, with four fictional seller accounts. Listings and sellers are labeled as demos. Repeating the command skips existing demo slugs and preserves existing listings.

```powershell
php artisan db:seed --class=DemoListingSeeder
```

The seed command copies 28 bundled WebP illustrations to the public storage disk. They work offline and are clearly marked as illustrative placeholders. Four listings have two images for the gallery. The fictional sellers use reserved `example.invalid` addresses and random passwords. Register your own local account to test selling and editing. See the [demo image notes](public/images/demo/README.md).

To review the design, check the homepage and browse filters at 375, 768, 1024, and 1440 pixels. Sign in and check the profile menu, Sell form, photo controls, and My Listings totals. Open a listing with multiple photos to switch between thumbnails, then confirm edit and delete actions appear only for its owner.

## Structure

- `routes/web.php` defines the home and authentication routes.
- `resources/views/home.blade.php` renders the homepage inside the shared marketplace layout.
- `app/Http/Requests` validates authentication forms. `app/Http/Controllers/Auth` handles account creation and sessions.
- `app/Models` defines Eloquent relationships, and `database/migrations` defines their database constraints. Listings derive the state and country through their city.
- `resources/views/components` contains the shared layout and form fields. `resources/css/auth.css` styles the authentication screens.

Build assets with `npm.cmd run build` before running `php artisan test`. The tests use an isolated in-memory SQLite database and require the `pdo_sqlite` PHP extension.

- [Architecture and database relationships](docs/architecture.md)
- [Assignment requirements and QA evidence](docs/phase-6-qa.md#assignment-requirement-traceability)
- [Initial commit and GitHub upload commands](docs/submission.md)
