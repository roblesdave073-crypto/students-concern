# Student Concerns — Supabase hosted version

The hosted site is the static app in supabase-app. It is designed to keep the InfinityFree landing page look and uses Supabase Auth plus Row Level Security for database access. GitHub Pages cannot run PHP, so the PHP source remains in the separate student_concern folder on this computer.

Use the same Supabase project already created. The earlier schema.supabase.sql is for a server-side PHP application and does not grant browser access. Run supabase-app/schema.sql in that existing project's SQL Editor to add the app's protected v2_ tables alongside the old schema. It preserves the existing tables.

GitHub Pages deploys automatically from main via the workflow in .github/workflows. The site is:
https://roblesdave073-crypto.github.io/students-concern/

After deployment, add that URL in Supabase Authentication → URL Configuration.
