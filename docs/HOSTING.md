# Free hosting for the Car Rental demo

This repository is a PHP + MySQL application, not a static frontend. **GitHub Pages alone cannot run it.**

## Recommended: InfinityFree

Official site: https://www.infinityfree.com/

Its free hosting plan advertises PHP, MySQL/MariaDB, SSL, free subdomains, and file uploads through FTP or a file manager. Limits and availability can change, so confirm them when creating an account.

Other options: [Byet Host](https://byet.host/free-hosting) (PHP/MySQL free plan), [AwardSpace](https://www.awardspace.com/) (smaller free PHP/MySQL plan).

**Why not Render as the first choice?** Render does not natively host PHP without Docker, its free web services can sleep, and its free managed Postgres expires after 30 days. This project uses MySQLi, not Postgres.

## Before publishing anything

1. **Rotate any database password that has appeared in this repository's Git history.** Replacing the current file does **not** remove historical copies. Consider coordinated history cleanup separately after making a backup and notifying collaborators.
2. **Do not deploy the example admin/customer passwords in `database/schema_and_seed.sql` to an internet-facing installation.** Create private demo-only credentials or a read-only showcase.
3. **Do not accept real credit card information.** The project has a local payment form that records demo transactions, **not** a certified payment gateway. Disable the card form or replace it with an explicitly marked dummy-payment flow before publishing.
4. Protect the admin area, disable/remove any troubleshooting endpoints you do not need, and verify account registration and upload controls.
5. Use HTTPS for the entire demo and test login/session behavior on the chosen provider.
6. Do not upload `.git/`, local passwords, private logs, or development backups.

## Deployment outline (after safety fixes)

1. Create a free hosting account and choose a free subdomain.
2. Create a MySQL database in the hosting panel. Record the *actual database host, database name, username and password* provided by the panel. The server is often **not** `localhost`.
3. Import `database/schema_and_seed.sql` with the provider's phpMyAdmin, **after replacing the seeded credentials/users for public use**.
4. Upload application directories (`admin/`, `php/`, `includes/`, `css/`, `js/`, `img/`, `html/`, `uploads/`) and `index.php` to the website document root.
5. Place a `config.local.php` containing the host-provided MySQL settings in the application root; PHP uses it on the server. **Keep it out of Git and protect it from public download.** If your provider supports environment variables, prefer those and omit this file.
6. Configure and verify HTTPS, then test authentication, available-car search, rentals, invoice pages, admin permissions, and uploaded images.
7. Do not enable public payment entry until the form is removed/replaced with a safe demo flow.

## Limitations of free hosting

- Providers may restrict CPU time, I/O, requests, file count, and outbound access.
- A free URL is suitable for an educational/portfolio **demo**, not a production rental business.
- Files and database credentials must be managed separately from GitHub; GitHub pushes are **not automatically deployed** to InfinityFree by default.
- Do not publish a live site link until functionality and security have been tested.

For local setup, see the [main README](../README.md).
