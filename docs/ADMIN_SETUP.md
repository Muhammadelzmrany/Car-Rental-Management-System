# Creating the first demo administrator

The hosting_demo.sql file deliberately creates no accounts. Local demo admin credentials do not work on the hosted database.

1. Open /php/loginview.php?show=signup on the hosted website and create your own account with a strong, private password.
2. In the hosting phpMyAdmin, open the database, then users > Browse.
3. Identify YOUR newly created account by its exact email address and id. Click Edit on that row.
4. Change only isadmin from 0 to 1, and save. Do not promote every account.
5. Sign out fully and sign back in. Admin permissions are loaded into the session at login.
6. Go to /admin/index.php. Confirm the dashboard loads for the admin.
7. Test while logged out and as a regular customer to ensure admin pages redirect to the sign-in page.

Do not put an admin password into GitHub or publish a privileged account on the portfolio site. Never build a public URL that promotes an account automatically.

## Session / CSRF troubleshooting

If signup, login, booking or demo payment says **Security token mismatch**:

1. Open the exact same HTTPS hostname throughout the flow (for example, `https://carrental-demo.great-site.net/`). Do not alternate HTTP and HTTPS or different subdomains.
2. After deploying the updated `includes/functions.php` and `php/loginview.php`, open a fresh login/signup page; don't submit an old tab or a page restored with Back.
3. In browser developer tools, check the request to `loginview.php` sets (or reuses) the `PHPSESSID` cookie and that the same cookie is sent to `signup.php`/`login.php`. Do **not** share cookie values.
4. If a fresh page still fails, check whether server sessions persist across requests and whether PHP can write to its session storage. Do not disable CSRF tokens to hide the problem.
5. After login, refresh or reopen the booking page before submitting an old form; login regenerates session IDs.
