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
