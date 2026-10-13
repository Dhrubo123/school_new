# Shobuj Ridge School & College

Phase 1 foundation for a Bangladeshi school website and role-based management system. The application uses Laravel 12, Inertia.js, React, TypeScript, Material UI, and Vite in one project.

## Local setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

`.env.example` is configured for MySQL. For a self-contained local or test run, set `DB_CONNECTION=sqlite` and create `database/database.sqlite`.

## Demo accounts

All seeded users use the password `School@123`.

| Role | Email | Portal |
| --- | --- | --- |
| Super Admin | superadmin@shobujridge.edu.bd | `/admin` |
| Admin | admin@shobujridge.edu.bd | `/admin` |
| Teacher | teacher@shobujridge.edu.bd | `/teacher` |
| Guardian | guardian@shobujridge.edu.bd | `/guardian` |
| Student | student@shobujridge.edu.bd | `/student` |

Public self-registration is disabled. Accounts are provisioned by school administrators.

## Quality checks

```bash
vendor/bin/pint --test
php artisan test
npm run build
npm audit --omit=dev
```

Notices and events are protected by policies and role middleware. Public pages only query items with `published` status whose publish date has arrived.
