<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# myportfolio-engine

---

## Course enquiries API (portfolio backend)

Endpoints:

| Method | Path | Notes |
|---|---|---|
| `GET` | `/api/health` | `{"status":"ok"}` |
| `POST` | `/api/course-enquiries` | JSON body `name, email, contact?, language (en\|km), format (online\|in_person\|either), level, message (≤2000), website (honeypot, must be empty)`. `201 {"message": "Thanks…"}`, `422` standard validation errors, `429` after 5 req/min/IP. |

On save, Telegram + email alerts are sent (sync by default; best-effort, failures are only logged).

### Admin API (Sanctum bearer token)

Create an admin user (prompts for password; re-run revokes all tokens):

```bash
php artisan admin:create you@example.com
```

| Method | Path | Notes |
|---|---|---|
| `POST` | `/api/admin/login` | `{email, password}` → `{token, user}`; token expires in 14 days |
| `POST` | `/api/admin/logout` | Revokes current token |
| `GET` | `/api/admin/me` | `{user}` |
| `GET` | `/api/admin/stats` | `{total, by_status}` |
| `GET` | `/api/admin/overview` | KPIs, 30-day daily counts, latest 5 enquiries |
| `GET` | `/api/admin/course-enquiries` | Paginated list; `?status=&search=&page=` |
| `GET` | `/api/admin/course-enquiries/{id}` | Single enquiry |
| `PATCH` | `/api/admin/course-enquiries/{id}` | `{status?, admin_note?}` |
| `DELETE` | `/api/admin/course-enquiries/{id}` | Soft-delete not used — hard delete |
| `GET` | `/api/admin/course-enquiries/export` | CSV download; same filters as list |

All `/api/admin/*` routes except login require `Authorization: Bearer <token>`. CORS must allow `PATCH` and `DELETE` (configured via `FRONTEND_URLS`).

### Local setup

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve            # http://127.0.0.1:8000
php artisan test             # uses in-memory sqlite
```

### Environment variables

| Var | Purpose |
|---|---|
| `FRONTEND_URLS` | Comma-separated CORS origins (default `https://roeun-vireak.mxlab.site,http://localhost:5173`) |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` | Telegram alerts; skipped (log warning) if empty |
| `ENQUIRY_NOTIFY_EMAIL` | Address for email alerts (needs working `MAIL_*` settings); skipped if empty |
| `ENQUIRY_NOTIFY_QUEUE` | `true` to send alerts via the queue (requires a worker). Default `false` |
| `ENQUIRY_RATE_LIMIT` | Requests per minute per IP (default 5) |

**Telegram bot:** message [@BotFather](https://t.me/BotFather) → `/newbot` → copy the token. Send any message to your new bot, then open
`https://api.telegram.org/bot<TOKEN>/getUpdates` and copy `result[0].message.chat.id` as `TELEGRAM_CHAT_ID`.

### Deploying on Coolify

- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` (generate with `php artisan key:generate --show`), `APP_URL`, plus the vars above.
- **Persistent DB:** either use a Coolify MySQL/Postgres service (`DB_CONNECTION`, `DB_HOST`, …) or, for SQLite, mount a persistent volume (e.g. `/var/www/html/database`) and set `DB_DATABASE` to the file inside it — otherwise enquiries are lost on every redeploy.
- **Run migrations on deploy:** add `php artisan migrate --force` as a post-deployment command (plus `php artisan config:cache && php artisan route:cache`).
- Behind Coolify's proxy, ensure the real client IP reaches Laravel (trusted proxies) so the per-IP rate limit isn't shared by all visitors.
- If `ENQUIRY_NOTIFY_QUEUE=true`, run a worker (`php artisan queue:work`) as a separate process.
