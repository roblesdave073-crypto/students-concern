# Student Concerns — GitHub Pages + Supabase

This is the GitHub-hosted version of the Student Concerns system. It keeps the InfinityFree landing page design and uses Supabase Auth and its Data API in the browser.

## Use the existing Supabase project

The app is already configured for the existing Supabase project using its URL and publishable key. The schema.supabase.sql you ran was designed for a PHP server using a server-only key. GitHub Pages cannot run PHP or safely use that server-only key.

To let this browser app work safely, open the same project’s **SQL Editor** and run [schema.sql](schema.sql). It adds separate v2_ tables and Row Level Security policies alongside your existing tables. It does not delete or overwrite those tables. Do not put a secret or service-role key in config.js.

## First administrator

1. Make sure email/password sign-in is enabled in Supabase Authentication.
2. Open the deployed site and register your own account as a student.
3. In Supabase SQL Editor, promote that account by replacing the email below with the one you registered:

~~~sql
update public.v2_profiles
set role = 'admin', is_active = true
where email = 'you@example.com';
~~~

Staff applicants stay inactive until an administrator approves them in **Accounts**.

## GitHub Pages

The repository workflow publishes this folder from the main branch.

1. In repository **Settings → Pages**, choose **GitHub Actions** as the source.
2. Wait for **Deploy Supabase app to GitHub Pages** to finish successfully.
3. Set the site URL in Supabase **Authentication → URL Configuration** and add it to Redirect URLs.

The site URL is https://roblesdave073-crypto.github.io/students-concern/.

## Notes

- This version uses the existing Supabase project, but does not copy records from the InfinityFree MySQL database.
- Attachments and SMTP email delivery are not included in this browser-only version; password reset uses Supabase Auth email settings.
