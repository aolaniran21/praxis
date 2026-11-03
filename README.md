# University Management

A server-rendered university management system built with Laravel 12, Blade, Tailwind CSS, Alpine.js, PostgreSQL, Breeze authentication, Spatie roles, and Pest.

## Requirements

- PHP 8.3+ with `bcmath`, `curl`, `mbstring`, `openssl`, `pdo_pgsql`, `tokenizer`, `xml`, and `zip`
- Composer 2
- Node.js 20+ and npm
- PostgreSQL 15+
- Docker Engine with Docker Compose (for containerized local development or Render deployment)

## Local setup

1. Create a PostgreSQL database named `university_management`.
2. Copy `.env.example` to `.env`; set the PostgreSQL credentials and a non-empty `APP_KEY`.
3. Configure `DEMO_PASSWORD` before running the local seeders.
4. Install and initialize:

   ```sh
   composer install
   php artisan key:generate
   php artisan migrate --seed
   npm install
   npm run build
   ```

   `composer setup` runs the same setup steps for a new local checkout.
5. Start the app:

   ```sh
   composer run dev
   ```

The local/testing seed creates demo roles, a faculty, department, program, academic session, courses, and role-specific accounts. The default local-only demo password is `ChangeMe123!`; set `DEMO_PASSWORD` to a unique value before seeding. Never seed demo accounts in production.

Demo accounts:

| Role | Email |
|---|---|
| Super admin | `admin@university.test` |
| Department admin | `department.admin@university.test` |
| Lecturer | `lecturer@university.test` |
| Student | `student@university.test` |

Public registration is disabled. Accounts and profiles must be provisioned by trusted administration; only the local/testing seeder currently creates demo users.

## Academic rules and implementation decisions

- One academic session may be active at a time, and one active semester may be current. Partial unique indexes protect both constraints in PostgreSQL and SQLite.
- Sessions and semesters require an end date on or after their start date; registration close must be later than registration open. A semester is not registrable unless it is active/current, its session is active, and registration is within its configured window.
- Levels are 100-900 in 100-level increments. Courses must match the student's current level and program department. Prerequisites require a published pass (score at least 40).
- The semester credit limit defaults to 24 and is configurable per semester. Registration additions, drops, and re-adds are allowed while registration is open. Drops are blocked after the deadline or once result entry starts; dropping and re-adding preserves the same registration record and writes an audit entry.
- Course assignments and academic records are archived/deactivated, not hard-deleted. Course details, assignment identities, session/semester dates, and department/program ownership cannot be changed after dependent registrations or profiles exist. Foreign keys restrict deletion of linked academic records.
- CA is scored out of 30 and examination out of 70. Scores are integers. Pass is 40. The grading scale is A (70-100, 5), B (60-69, 4), C (50-59, 3), D (45-49, 2), E (40-44, 1), F (0-39, 0).
- GPA/CGPA uses published results only, weighted by course credits, and displays two decimal places using standard half-up rounding. For retakes, the latest published attempt by semester start date counts once per course.
- Department admins may approve, reject, publish, and request a correction only for their own department. Super-admins have university-wide setup/report oversight, not result approval. A published correction requires a reason, hides the result from the student while reopened, returns it to an assigned lecturer's draft, and must pass through submission, approval, and publication again. The old scores and correction reason remain in the audit history. There is no direct approval reversal.
- Reports default to published results and can filter by session, semester, department, program, level, course, and result status. Semester GPA is shown for a selected semester using published results.
- Deactivating accounts blocks login. Super-admins cannot deactivate themselves or the last active super-admin.

These are assessment defaults, not institution-approved policy. Confirm them with the university before production use.

## Implemented workflows

- Shared user authentication with separate student and lecturer profiles.
- Department-scoped roles and resource policies; super-admin oversight does not grant result approval.
- Super-admin setup for faculties, departments, programs, sessions, semesters, courses, and lecturer assignments; super-admins can provision user accounts with the required profiles.
- Academic setup and user administration support search and pagination; result reports provide scoped filters and pagination.
- Department admins can create and maintain department courses, archive them without deleting history, and assign department lecturers.
- Super-admins can edit/archive academic records and manage user details, profiles, and account status. Department admins can update/archive scoped courses and assignments.
- Academic sessions, semesters, and prerequisite relationships are enforced in the registration workflow.
- Student registration summaries can be printed. Students may drop/re-add before the deadline if result entry has not started; changes are audit logged.
- Student result statements show CGPA and can be printed; super-admins and department admins can filter authorized result reports, including semester GPA, and export the same filtered data to CSV.
- Transactional course registration with active-period, program/department, level, prerequisite, duplicate, and credit-limit checks.
- Result drafts, lecturer submission, department approval/rejection with required feedback, publication, audited post-publication correction, and transition audit history.
- Published-result statements and CGPA based on the latest published attempt per course, weighted by credit units.
- Grade scale: A (70-100, 5), B (60-69, 4), C (50-59, 3), D (45-49, 2), E (40-44, 1), F (0-39, 0). CA is scored out of 30 and the examination out of 70.

## Tests and verification

```sh
vendor/bin/pest
vendor/bin/pint --test
npm run build
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan migrate:fresh --seed --force
```

Feature tests use an isolated in-memory SQLite database. `pdo_sqlite` is required for tests; `pdo_pgsql` is required for the application. Do not point tests at a production database.

## Docker and Render deployment

The production Docker image builds frontend assets, installs Composer production dependencies, and serves Laravel's `public/` directory with Nginx and PHP-FPM on PHP 8.3 Alpine. Supervisor keeps Nginx and PHP-FPM running in the same container. It listens on Render's `PORT` (default `10000`) and uses Laravel's `/up` health endpoint.

Run the app with PostgreSQL locally using Docker Compose:

1. Generate a local application key and export it for Compose:

   ```sh
   export APP_KEY="$(php artisan key:generate --show)"
   docker compose up --build
   ```

2. Visit `http://localhost:8000`. Compose starts PostgreSQL, waits for it to be ready, applies migrations, seeds the local demo university and role permissions, and starts Nginx/PHP-FPM under Supervisor. Sign in with `admin@university.test`, `department.admin@university.test`, `lecturer@university.test`, or `student@university.test`; the default local-only password is `ChangeMe123!`. Set `DEMO_PASSWORD` in your shell before starting Compose to use a different password. The default password applies to local seeding only.
3. Stop the containers with `docker compose down`. Add `--volumes` only if you intend to delete the local PostgreSQL data.

Deploy to Render:

1. Push this project to a Git provider connected to Render, then create a Blueprint from the repository using `render.yaml`.
2. The Blueprint creates a Docker web service in Oregon and prompts for `DB_URL` and `DEMO_PASSWORD`. In Render's environment settings, provide your Neon PostgreSQL connection URL and a strong, unique demo password; `DB_EMULATE_PREPARES=true` supports transactional migrations over Neon/PgBouncer pooler connections. The pre-deploy command migrates the database and seeds role/permission definitions, demo academic records, and four demo accounts.
3. When prompted, set `APP_KEY` using the output of `php artisan key:generate --show`. Do not change this key after deployment, and never use the local/demo key in production.
4. Demo sign-in emails are `admin@university.test`, `department.admin@university.test`, `lecturer@university.test`, and `student@university.test`; all use the `DEMO_PASSWORD` secret. These are demonstration accounts, not real institutional users.
5. Render provides `RENDER_EXTERNAL_URL`; the container uses it as `APP_URL` when one is not explicitly configured. Set production mail credentials and any institution-specific environment values in the Render dashboard.
6. After deploy, confirm `/up` is healthy and test login and the role-specific workflows. Configure backups and verify a restore; Neon backup features and retention depend on the selected database plan.

The `render.yaml` database and web-service plans may be adjusted to fit budget and capacity. The app container has an ephemeral filesystem, so user uploads must use object storage or another persistent service if added later.

## VPS deployment

1. Provision PHP 8.3+ with `bcmath`, `curl`, `mbstring`, `openssl`, `pdo_pgsql`, `tokenizer`, `xml`, and `zip`; Composer 2; Node.js 20+; npm; and PostgreSQL 15+. Create a least-privilege PostgreSQL database/user and verify network access from the application host.
2. Deploy source outside the public web root. Create `.env` on the server from `.env.example`; set unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, `APP_TIMEZONE`, PostgreSQL credentials, production mail transport, secure session settings, and production log level. Never copy a local `.env` or demo password to production.
3. Install/build and migrate:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

   Run `key:generate` only when provisioning a new application key; preserve the existing key when deploying updates. Never run the demo seeder in production.
4. Configure Nginx/Apache's document root to the application's `public/` directory, route requests through `public/index.php`, deny access to dotfiles and source/configuration files, and enable TLS/HTTPS. Grant the web user write access only to `storage/` and `bootstrap/cache/`.
5. Configure scheduled `php artisan schedule:run` once per minute only when scheduled tasks are introduced. Start `php artisan queue:work --tries=3` under systemd/Supervisor if queued work is enabled. Restart workers after deploys.
6. Configure encrypted/off-host PostgreSQL backups, retention, periodic restore drills, storage monitoring, log rotation, HTTPS renewal, and a rollback procedure. Verify login, role boundaries, registration, result approvals/publication, corrections, report scoping, and CSV export on the deployed host.

Example backup command (run under a restricted service account and send output to protected storage):

```sh
pg_dump --format=custom --no-owner --file=/protected-backups/university-$(date +%F).dump university_management
```

### Handoff limitations

- PHP 8.3.6, required PHP extensions, Composer 2.7.1, Node.js 25, and npm 11 are available in the development environment.
- Automated tests currently run on SQLite. PostgreSQL connectivity and migrations still require valid database credentials; no production VPS or TLS endpoint is available here, so deployment verification must be completed in the target environment.
- Demo users and synthetic data are for local/testing only. Set a unique `DEMO_PASSWORD` before local seeding; never seed demo users in production.
