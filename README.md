# AI News Bot

Laravel Cloud-ready rewrite of the original Python Discord AI news bot.

The original VPS/Docker implementation is preserved in `legacy-python/`. This Laravel version is designed to run as a scheduled job instead of a constantly connected Discord gateway process.

## What It Does

- Fetches AI news from the same RSS feeds as the Python bot.
- Groups articles into the same four categories.
- Posts a Discord embed digest to `DISCORD_CHANNEL_ID`.
- Stores posted article links in the database to avoid duplicates.
- Runs automatically twice daily through Laravel Scheduler.
- Can be run manually with `php artisan news:post`.

## Key Difference From The Python Bot

The old bot used `discord.py` and stayed online in Discord, which made the `!force_news` command possible but required an always-on process.

This Laravel version uses Discord's REST API and Laravel Scheduler. That is a better fit for Laravel Cloud and cheaper to run, but it does not keep a live Discord command listener connected.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

For local testing you can use SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Then create the database file and migrate:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
```

## Required Environment Variables

```env
DISCORD_TOKEN=
DISCORD_CHANNEL_ID=
AI_NEWS_SCHEDULE_TIMEZONE=America/New_York
AI_NEWS_MAX_AGE_DAYS=7
AI_NEWS_MAX_PER_CATEGORY=5
AI_NEWS_MAX_TOTAL_ARTICLES=15
AI_NEWS_MAX_EMBED_PAGES=3
```

For Laravel Cloud, use Postgres or MySQL. Do not use SQLite in production.

## Commands

Post to Discord:

```bash
php artisan news:post
```

Build the digest without sending to Discord or writing posted articles:

```bash
php artisan news:post --dry-run
```

Run tests:

```bash
composer test
```

## Laravel Cloud Deployment Notes

1. Create/import the app in Laravel Cloud from this repository.
2. Add a managed Postgres or MySQL database.
3. Set the database environment variables from Laravel Cloud.
4. Set `DISCORD_TOKEN` and `DISCORD_CHANNEL_ID`.
5. Run migrations during deploy with `php artisan migrate --force`.
6. Ensure Laravel Scheduler is enabled for the app.
7. After verifying Laravel Cloud posts successfully, stop the Hostinger `ainewsbot` container.

The Hostinger bot should remain live until the Laravel Cloud version has posted at least one successful digest.
