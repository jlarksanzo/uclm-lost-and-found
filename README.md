# UCLM Lost & Found — IT ELECT1

## Ready for October 26, 2026
Registration, login, logout, protected dashboard, SQLite persistence, hashed passwords, CSRF protection, validation, and request throttling. Lost/found reports and admin moderation are implemented in milestone 2 below. Ownership claims are future work. This is a classroom prototype, not an official university service. Email ownership and student status are not verified.

## Run on this laptop
Double-click START.cmd in this folder. Keep its window open. Visit http://127.0.0.1:8000. If the server is already running, simply visit the address. Press Ctrl+C in the server window to stop it.

## Run on another laptop
Install PHP 8.3+ and Composer from their official websites. Enable curl, fileinfo, mbstring, openssl, pdo_sqlite, sqlite3, zip, dom and xml. From the project folder:

    composer install
    copy .env.example .env
    php artisan key:generate

Create an empty database/database.sqlite file if it is missing, then run:

    php artisan migrate
    php artisan serve

Use SQLite in .env. No npm build or MySQL server is needed for these Blade pages; styling is public/css/app.css. Do not use composer run dev for this milestone.

## Demonstration
1. Open the registration form and create a fictional test account.
2. Show the dashboard and account email.
3. Log out and open /dashboard; it should redirect to login.
4. Try a wrong password and show the error.
5. Log in with the correct password.
6. Log out, then attempt registration using the same email; show rejection.
7. Show the group's real GitHub commits and explain each member's contribution.

## Group work and GitHub
Create a repository named uclm-lost-and-found in your GitHub account. Add members under Settings > Collaborators. Initialize Git in this project folder only, then use the instructions GitHub displays to push it. Do not initialize Git in sources/ or the parent synced project folder.

The included .gitignore excludes .env, vendor/, databases and local runtime files. Commit composer.lock. Never upload passwords, .env, database contents or work/runtime. Members should use their own GitHub accounts and commit work they actually completed. AI-generated starter code is not evidence of each student's individual work. Follow your instructor's AI-use rules.

Suggested tasks to divide based on group size: registration form and validation; login/logout and sessions; layout and dashboard; testing and documentation. Each member should review their code and be able to explain it.

## Code map
- routes/web.php: URLs and guest/auth middleware.
- app/Http/Controllers/AuthController.php: registration, login and logout.
- app/Models/User.php: allowed fields and automatic password hashing.
- resources/views/auth/: login and registration forms.
- resources/views/dashboard.blade.php: signed-in page.
- public/css/app.css: responsive styling.
- tests/Feature/AuthenticationTest.php: authentication checks.

## Tests
    php artisan test

Latest result: 14 tests passed, 89 assertions, including the default Laravel tests. SQLite test data is kept in memory, separate from the demo database.

## Learning references
- https://www.youtube.com/watch?v=f1hCx-NXbek — instructor's Breeze/Blade tutorial.
- https://www.youtube.com/watch?v=_LA9QsgJ0bw&t=726s — instructor's CRUD tutorial.
- https://laravel.com/docs/13.x/authentication — framework authentication documentation.

This starter uses Laravel 13 and built-in authentication with Blade. It does not reproduce the older Breeze tutorial step for step. Ask your instructor whether a specific Laravel version or exact tutorial implementation is required. The linked my_app sample has not been inspected.

## Milestone 2 — Item reports and admin review
Completed: authenticated browsing/search (item name or location), lost/found filter, report submission with an optional JPG/PNG/WebP photo up to 2 MB, My reports, admin approval/rejection, and marking approved reports as returned. Pending and rejected reports/photos are visible only to their owner or an admin. Approved reports are visible to signed-in users. Ownership claims and messaging are not implemented yet.

Register a normal account first. To grant admin access, run from this folder:

    php artisan app:make-admin your-registered-email@example.com

On this laptop, if PHP is not on the command path, run in PowerShell:

    & ../../work/runtime/php/php.exe artisan app:make-admin your-registered-email@example.com

Use the actual registered email, then refresh the dashboard. Admin navigation appears automatically. There is no preset admin password and no public role selector. To demonstrate review, submit a report, sign into the admin account, open Admin, approve it, then browse items. Mark returned only after the physical handover is verified. Rejected reports remain available in My reports; editing/resubmitting is future work.

For other group laptops after pulling these changes:

    php artisan migrate

Photos are stored privately in storage/app/private/reports and served through an authenticated route; no storage link is needed. The local development server is for demonstrations on this laptop. Do not commit uploaded photos. Latest test result: 14 tests passed, 89 assertions.

