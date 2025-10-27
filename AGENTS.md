# Repository Guidelines

## Project Structure & Module Organization
Laravel services live in `app/` (HTTP controllers, models, jobs) with configuration in `config/` and route definitions in `routes/` (web/auth/settings). Inertia-powered Vue screens are arranged in `resources/js/pages` to mirror route names, while reusable UI resides under `resources/js/components` and shared logic in `resources/js/composables`. Database migrations, seeders, and factories are in `database/`, and backend tests are split between `tests/Feature` for HTTP flows and `tests/Unit` for focused logic.

## Build, Test, and Development Commands
Start fresh by running `composer install` and `npm install`. `php artisan migrate` (sqlite is preconfigured at `database/database.sqlite`) keeps schemas current. Use `composer dev` to launch the Laravel server, queue listener, log stream, and Vite in one process; `npm run dev` is available when you only need the front-end. `npm run build` creates the production asset bundle and `npm run build:ssr` adds the SSR build. `composer test` (or `php artisan test`) runs the PHP suite.

## Coding Style & Naming Conventions
Adhere to PSR-12 formatting and run `./vendor/bin/pint` before committing backend changes. Keep controllers and services focused, pushing view data to Inertia resources when possible. Vue and TypeScript files are auto-formatted with Prettier (2-space indent) and linted via `npm run lint`; components in `resources/js/components` should use PascalCase filenames, and composables should start with `use`. Tailwind classes are organized with the Tailwind/Prettier plugins; avoid hand-sorting.

## Testing Guidelines
Add a feature test for every new route or major acceptance path (`tests/Feature/DashboardTest.php` shows the expected structure). Unit-test reusable services in `tests/Unit`, keeping class names aligned with the subject under test. `php artisan test --coverage` is available locally; aim to cover new behaviors and any encryption-critical code paths. When database state matters, prefer factories and the `RefreshDatabase` trait.

## Commit & Pull Request Guidelines
Write commit subjects in the present tense and keep them descriptive (existing history favors sentence-style summaries such as “Refactor Answer export handling”). Squash noisy commits before opening a PR. Every PR should include: a concise summary, linked issue ID or task reference, steps to reproduce/verify, screenshots or GIFs for UI changes, and notes about migrations, queues, or environment variables. Mention any security-sensitive adjustments, especially around cryptography or key management.

## Security & Configuration Tips
Never commit `.env`; instead copy `.env.example` and run `php artisan key:generate`. Keep OpenPGP and storage keys in your local vault and do not log decrypted payloads. When debugging, use `php artisan pail` from `composer dev` rather than inserting `dd()` calls into request paths.
