# zf1-future Demo App

This repository contains a minimal Zend Framework 1 Future (ZF1-Future) application that runs on PHP 8.1 inside Docker. The stack ships with a Bootstrap 3 + jQuery landing page that links to useful framework and tooling resources.

## Getting Started

1. Install dependencies (uses the official Composer image so no host PHP is required):
   ```bash
   docker run --rm -v $(pwd):/app composer install
   ```
2. Launch the application:
   ```bash
   docker-compose up --build
   ```
   The site becomes available at <http://localhost:8000>. Stop containers with `docker-compose down`.
3. Update dependencies later with the same Composer command. If you need a clean slate, run `docker-compose down --volumes` before reinstalling.

## Project Layout

- `application/` – MVC code (`controllers/`, `views/`, `layouts/`, and configuration under `configs/`).
- `public/` – front controller (`index.php`) and `.htaccess` rewrite rules served by Apache.
- `vendor/` – managed by Composer; omit from commits (see `.gitignore`).
- `Dockerfile` / `docker-compose.yml` – PHP 8.1 Apache image and service definition mapping container port 80 to host port 8000.

## Development Notes

- Follow the contribution rules in `AGENTS.md` (coding style, testing, commit conventions).
- Controllers should extend `Zend_Controller_Action`; add matching view scripts under `application/views/scripts/{controller}/{action}.phtml`.
- Style UI components with Bootstrap 3; jQuery is available for interactivity via CDN includes in the layout template.
- Add Composer scripts (for tests, code quality, etc.) under the `scripts` section in `composer.json`, then execute them using `docker run --rm -v $(pwd):/app composer <script>`.

## Commit Expectations

Each commit must include:
- A short imperative subject line.
- An English body paragraph covering motivation, key changes, and test evidence (even if brief). See `AGENTS.md` for full guidance.
