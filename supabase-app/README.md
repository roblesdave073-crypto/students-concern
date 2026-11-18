# GitHub Pages + Supabase version

This is a separate static version of Student Concerns. The PHP application and its InfinityFree/MySQL database remain at the repository root and are not used by this app.

## 1. Create the Supabase tables

In your Supabase project, open **SQL Editor → New query**. Paste and run [`schema.sql`](schema.sql). The `v2_` table names keep this version separate from any earlier experimental tables in the project.

## 2. Configure the browser client

In Supabase, open **Project Settings → API** and copy the **Project URL** and the **publishable key** (or legacy anon key). Put them in [`config.js`](config.js):

```js
export const SUPABASE_URL = 'https://YOUR_PROJECT_REF.supabase.co';
export const SUPABASE_PUBLISHABLE_KEY = 'YOUR_PUBLISHABLE_OR_ANON_KEY';
```

The publishable/anon key is designed to be present in browser code. Access is restricted by the SQL policies. **Never use a secret/service-role key in this file.**

## 3. Create the first administrator

1. Enable email/password sign-in in Supabase Authentication.
2. After the site is deployed, register your own account as a student and confirm the email if confirmation is enabled.
3. In the Supabase SQL Editor, promote that account. Replace the email with the exact address you used:

```sql
update public.v2_profiles
set role = 'admin', is_active = true
where email = 'you@example.com';
```

Staff applicants register as staff and remain inactive until an administrator approves them in **Accounts**.

## 4. Publish with GitHub Pages

The workflow at `.github/workflows/deploy-supabase-pages.yml` publishes this folder when it changes on `main`.

1. Push the workflow and `supabase-app` folder to GitHub.
2. In the repository, open **Settings → Pages** and choose **GitHub Actions** as the source.
3. Wait for the **Deploy Supabase app to GitHub Pages** workflow to finish. The site URL appears in the workflow summary (usually `https://OWNER.github.io/REPOSITORY/`).
4. In Supabase **Authentication → URL Configuration**, set that site URL as the Site URL and add it to the Redirect URLs.

## Notes

- GitHub Pages serves static HTML, CSS, and JavaScript. This version uses Supabase Auth and the Supabase Data API directly from the browser, protected by RLS.
- The Supabase project is separate from the InfinityFree MySQL database. No existing InfinityFree records are copied or changed.
- This version does not implement attachment storage or email delivery for notifications. Password reset links use Supabase Auth email settings.
- Configure SMTP and email confirmation settings in Supabase before inviting real users.
