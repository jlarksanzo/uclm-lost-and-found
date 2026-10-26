# UCLM Lost & Found — IT ELECT1

Laravel 13 / PHP 8.3+ / Blade / SQLite. A student prototype for the University of Cebu Lapu-Lapu and Mandaue, not an official university service.

## Run on the original development laptop
Double-click START.cmd and keep the terminal open. Open http://127.0.0.1:8000. START.cmd uses the local PHP runtime if available, otherwise PHP on your command path.

## First setup on another laptop
Install PHP 8.3+ and Composer. Enable the PHP extensions required by Composer, including pdo_sqlite, sqlite3, mbstring, fileinfo, openssl and curl. Open PowerShell in this project folder:

    composer install
    Copy-Item .env.example .env
    php artisan key:generate
    if (!(Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File }
    php artisan migrate
    php artisan serve

Run the copy/key commands only for a fresh setup. Do not overwrite an existing .env or database. No npm build or MySQL server is needed for these Blade pages. Opening XAMPP alone does not start Laravel. Each laptop has its own database; GitHub shares code, not live accounts and reports.

## After pulling updates

    composer install
    php artisan migrate
    php artisan view:clear
    php artisan serve

The October 27 migration preserves existing reports and puts their category in Other. Older returned reports remain returned; a historical handover record is not invented for them.

## Features
- Registration, login, logout, protected pages and request limits.
- Lost/found reports with category, description, location, date, and optional JPG/PNG/WebP photo up to 2 MB.
- Browse approved items by keywords, lost/found type, and category.
- My reports: open details, check status, and edit your own reports. Editing returns a report to pending review. Reports with pending/approved claims, or a completed return, cannot be edited.
- Found-item claims: signed-in users submit private proof. A user cannot claim their own report. One claim per user per item; an approved claim blocks new claims.
- My claims: see your proof, status, and admin review note. Proof is visible only to the claimant and admins, not to other students or the finder.
- Admin review: approve/reject reports and claims. Claim review requires a note. Admins cannot review their own ownership claims. Only one claim can be approved per item.
- Return recording: approval alone is not a physical return. After handover, an admin enters a note and confirms ownership verification. Found reports require an approved claim; lost reports are recorded as returned to their reporting user. Other pending claims close on return.
- Admin return history records the item, recipient, handling admin, note and timestamp. Completed returns cannot be edited or recorded twice.
- Updates: in-system notifications for new/edited reports (admins), new claims (admins), review decisions (affected user), and returns. Mark-all-read affects only your own notifications. No email or SMS is sent.
- Possible matches appear in your report details. These use shared keywords, category and location from approved opposite-type reports. Suggestions are not ownership verification and do not use an external AI service.

## Admin setup
Register an account normally. Grant access from the project folder:

    php artisan app:make-admin your-registered-email@example.com

On the original laptop, if PHP is not on the command path:

    & ../../work/runtime/php/php.exe artisan app:make-admin your-registered-email@example.com

No preset admin credentials are provided. Students cannot select an admin role during registration. Admin > Reports / Claims / Return history opens the review tools.

## Demo sequence
1. Register a finder, an owner, and a separate admin account; grant only the admin access.
2. As the finder, submit a found-item report with category and photo.
3. As admin, approve the report.
4. As the owner, browse the item and submit private proof.
5. As admin, review the proof and approve the claim with a note.
6. Verify the owner's Updates and My claims pages.
7. Complete physical handover; as admin, open the item details, enter a note, confirm and record return.
8. Verify Return history and that the item no longer appears in Browse.
9. Separately test editing an unclaimed report and its return to pending review.

Use fictional classroom data. Do not upload sensitive IDs, passwords, or private details in public descriptions/photos. Email ownership and student status are not verified in this prototype. The rules-based matching can miss matches or suggest unrelated items; admin verification is required.

## Data and GitHub
Commit source and composer.lock. Do not commit .env, vendor/, SQLite databases, runtime tools, uploaded photos, session files, or private proof. Uploaded photos are served through authenticated routes and are stored in storage/app/private/reports; no public storage link is needed. Back up the database and uploaded photos privately when transferring a demonstration.

Each group member should use their own GitHub identity, commit work they actually completed, and explain it during progress checks. Treat this AI-assisted starter as the shared starting point. Follow your instructor's AI-use rules.

## Code map
- routes/web.php: authenticated routes.
- app/Http/Controllers/AuthController.php: login, registration and logout.
- app/Http/Controllers/ItemReportController.php: reports, filters, edits and moderation.
- app/Http/Controllers/ClaimController.php: private claims, review, returns and history.
- app/Http/Controllers/NotificationController.php: user notifications.
- app/Models/ItemReport.php: report relationships, access and simple matching.
- resources/views/: Blade pages; public/css/app.css: responsive styles.
- database/migrations/: schema changes; tests/Feature/: workflow and access checks.

## Validation

    php artisan test

Latest result: 23 tests passed, 167 assertions. Tests use a separate in-memory SQLite database.

## References
- Instructor login tutorial: https://www.youtube.com/watch?v=f1hCx-NXbek
- Instructor CRUD tutorial: https://www.youtube.com/watch?v=_LA9QsgJ0bw&t=726s
- Laravel authentication: https://laravel.com/docs/13.x/authentication

This uses current Laravel authentication tools rather than reproducing the older Breeze tutorial step for step. The linked my_app reference has not been inspected.
