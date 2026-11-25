# MarketNest Phase 6 QA report

**Status** Completed on November 25, 2026. All mandatory assignment requirements passed. The isolated test suite passed 53 tests with 427 assertions and zero failures. MySQL 8.0.42 and headless Edge checks passed separately.

## Assignment requirement traceability

| Requirement | Implementation | Automated evidence | Status |
| --- | --- | --- | --- |
| User registration | [Registration controller](../app/Http/Controllers/Auth/RegistrationController.php), [validation](../app/Http/Requests/RegisterRequest.php) | `AuthenticationTest` checks account creation, hashing, validation, redirect to login, and manual sign-in. | Passed in SQLite, MySQL and Edge |
| Product listing | [Listing controller](../app/Http/Controllers/ListingController.php), [publishing service](../app/Services/ListingPublisher.php) | `ListingManagementTest` creates an owned product and checks photo storage and its public pages. | Passed in SQLite, MySQL and Edge |
| Service listing | [Listing controller](../app/Http/Controllers/ListingController.php), [publishing service](../app/Services/ListingPublisher.php) | `ListingManagementTest` creates a service without photos and checks the placeholder. | Passed in SQLite and MySQL |
| All ten required fields | [Sell form](../resources/views/listings/form.blade.php), [listing validation](../app/Http/Requests/ListingRequest.php) | `ListingManagementTest` checks type, name, description, category, subcategory, country, state, city, area, and price, including mismatched category and location records. | Passed in SQLite. MySQL accepted both listing types with all ten fields. Edge exercised the full dropdown chain. |
| Immediate public visibility | [Homepage](../resources/views/home.blade.php), [listing controller](../app/Http/Controllers/ListingController.php) | `ListingManagementTest` checks the homepage and public detail after publication. | Passed in SQLite and MySQL |
| Category page | [Browse controller](../app/Http/Controllers/ListingBrowseController.php), [routes](../routes/web.php) | `ListingBrowseTest` checks category and subcategory results, invalid slugs, and attempts to override the category. | Passed in SQLite, MySQL and Edge |
| City page | [Browse controller](../app/Http/Controllers/ListingBrowseController.php), [routes](../routes/web.php) | `ListingBrowseTest` checks city and area results, invalid slugs, and attempts to override the city. | Passed in SQLite, MySQL and Edge |
| Combined city and category page | [Browse controller](../app/Http/Controllers/ListingBrowseController.php), [listing query](../app/Models/Listing.php) | `ListingBrowseTest` verifies both constraints apply, with fixtures that match only one constraint, and checks combined search, price, and type. | Passed in SQLite, MySQL and Edge |
| Individual listing details | [Detail view](../resources/views/listings/show.blade.php), [listing controller](../app/Http/Controllers/ListingController.php) | `ListingManagementTest` and `ListingBrowseTest` verify photo, title, description, location, seller privacy, detail links, and missing-slug handling. | Passed in SQLite, MySQL and Edge |

Feature tests are in [tests/Feature](../tests/Feature). PHPUnit forces in-memory SQLite, and [Tests\TestCase](../tests/TestCase.php) rejects an unexpected connection before migrations run.

## Confirmed issues and fixes

| ID | Module and severity | Steps to reproduce | Expected and observed behavior | Root cause and fix | Verification |
| --- | --- | --- | --- | --- | --- |
| QA-01 | Browse validation · Medium | Request browse results with an invalid price range and an `Accept: application/json` header. Repeat from the Blade form with a valid keyword and minimum price. | JSON clients should receive field errors. The HTML form should retain valid selections. Both requests previously returned HTML, and the form cleared the valid selections. | The controller always rendered Blade and replaced every filter with an empty array on validation failure. It now returns JSON field errors with HTTP 422 when requested and keeps valid Blade inputs for correction. | Regression test passed for both response formats. |
| QA-02 | Route binding · Medium | Request an option endpoint with a seeded numeric city ID followed by a text suffix. | A malformed ID should return HTTP 404. MySQL returned HTTP 200 before the fix. | MySQL coerced the mixed value during numeric ID comparison. Numeric route constraints now cover dropdown IDs and the ID-based listing management URLs. | Baseline HTTP 200 and corrected HTTP 404 reproduced on MySQL. The automated route test passed. |
| QA-03 | Listing photos · Medium | Open a test listing whose image record points to a missing file. | The listing should display the local placeholder. The browser previously displayed a broken image. | The template handled absent image records only. The browser now switches failed image loads to the local placeholder and updates the alternative text. | Edge verified the placeholder loaded for a missing photo. |
| QA-04 | Storage cleanup · Medium | On isolated test storage, attach legacy metadata that points to an unrelated local fixture and delete that listing. | Listing cleanup should leave the unrelated file intact. The cleanup code previously passed stored paths directly to the public disk. | The service now restricts cleanup to supported files in the managed listings directory. Public image URLs use the same restriction. | The isolated regression test confirmed removal of the association and preservation of the unrelated file. The live audit found zero out-of-scope image paths. |
| QA-05 | Listing publication · Low | Publish a listing without photos. | Publication should avoid querying image records. The service previously queried the removal selection, image count and ordering even with no photo changes. | Image work now runs only when requested. Browse city options also use one joined query and reuse loaded country and state names. | The MySQL query log showed zero image-table queries for photo-free publication. |

## Security and error handling

- Authenticated writes are protected by Laravel middleware and server-side ownership checks. Tests confirm that another seller cannot edit or delete a record or remove its images. Submitted ownership fields are ignored in favor of the authenticated user.
- Registration stores a Laravel password hash and keeps the user signed out. Login rate limiting, session rotation, remember-me cookies, and logout invalidation passed automated tests. The intended Sell redirect passed in SQLite and MySQL.
- Blade escapes listing text. Eloquent binds search terms and filter values, and sorting accepts only supported keys. Seller details expose the name without including the private email address.
- Test coverage includes HTTP 403, 404, 419, JSON 422, Blade validation redirects, and generic production HTTP 500 and 503 responses. The 503 connection-failure behavior was verified with an isolated simulated database exception. Production requires `APP_DEBUG=false`.
- Upload tests cover real file format checks, executable-filename rejection, size limits, the five-image limit, distinct filenames, cross-listing image removal, and cleanup after a failed database write. MySQL verified two uploads, removal during editing, and deletion cleanup. A separate read verified a real public photo through the storage junction.
- `.env` and `public/storage` are ignored. Composer and npm reported zero known advisories for the locked dependencies at the time of this check.

## MySQL and performance

All five development migrations were present. The read-only integrity audit confirmed primary keys, scoped uniqueness, foreign keys, a `DECIMAL(12,2) UNSIGNED` listing price, and indexes covering ownership, category, subcategory, city, area, price, and publication time. It found zero orphaned relationship records and zero missing files referenced by current listing images.

The transactional integration check covered registration, intended login, product and service publication, image upload and removal, homepage visibility, category, city and combined browse pages, detail, keyword and price filters, sorting, editing, deletion, and logout. Default category and location seeders produced stable counts on repeat runs. Temporary MySQL rows were rolled back.

MySQL logged 11 queries for one browse result and 11 for 12 results in the measured search scenario. Card relationships are eager-loaded, results are paginated in groups of 12, and card images use lazy loading. No migration or new infrastructure was required.

## Browser verification

Headless Edge rendered the homepage, login, registration, Sell, all listings, category, city, combined browse, public detail, My Listings, Edit, and the 404 page at **375, 768, 1024, 1440, and 1920 px** without detecting horizontal overflow. The run checked listing image aspect ratios, a long listing name, the visible keyboard skip link, dependent selects, validation messages and restored values, photo preview and removal, public publication, a failed image fallback, empty search results, and Clear filters. Authenticated browser actions used a temporary SQLite database.

## Commands and setup checks

| Command or check | Result |
| --- | --- |
| `php artisan test --compact --no-ansi` | 53 passed, 0 failed, 427 assertions |
| `php vendor/bin/pint --test`, `npm.cmd run build`, JavaScript syntax checks | Passed |
| `composer validate --strict`, `composer check-platform-reqs`, `composer install --dry-run --no-interaction` | Passed for the current platform and lock file |
| `composer audit --locked --format=table`, `npm.cmd audit --json --fetch-retries=0` | Zero advisories. Composer printed a Git ownership warning when run outside the sandbox and exited successfully. |
| `php artisan route:list`, `php artisan migrate:status`, `php artisan view:cache`, `php artisan view:clear` | Passed |
| `migrate --seed` through Artisan on fresh in-memory SQLite | Passed. Created 7 categories, 6 cities, and 15 areas. |
| `php artisan serve` and public storage verification | Local server and image delivery passed. Existing storage junction points to the intended public disk. |

The checked-in lock files, safe [environment template](../.env.example), and [setup instructions](../README.md) are ready for local onboarding. The install check used Composer’s dry run on this machine and a fresh SQLite schema. The optional demo listing seeder remains unrun.

## Manual submission checklist

- Open the marketplace on a physical phone and check navigation wrapping, touch targets, and photo thumbnails.
- On the configured PHP server, select up to five photos, including one near the 5 MB limit. Confirm upload and image delivery, then switch gallery thumbnails.
- From an owner account, open the delete confirmation, cancel once, then confirm deletion on a disposable listing.
- On a fresh local setup, generate an app key, provision the MySQL account, migrate and seed, link public storage, build assets, and complete the registration and Sell flow.

The application is ready for the assignment’s packaging review. No blocking defects remain in the verified Phase 6 scope.
