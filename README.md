# AI News Bot

A Discord bot that fetches AI news from various RSS feeds and posts them to a configured channel.

## Setup

1.  **Install Python 3.8+**
2.  **Install Dependencies:**
    ```bash
    pip install -r requirements.txt
    ```
3.  **Configure Environment:**
    - Rename `.env.example` to `.env` (if not already done).
    - Add your `DISCORD_TOKEN` and `DISCORD_CHANNEL_ID`.
      - To get `DISCORD_CHANNEL_ID`, enable Developer Mode in Discord settings, right-click the channel, and select "Copy ID".

## Usage

Run the bot:

```bash
python -m src.main
```

## Features

- **Automatic Updates:** Checks for news every 12 hours (configurable).
- **Consolidated Posts:** Groups new articles into a single, organized daily summary.
- **Manual Trigger:** Use `!force_news` to test immediately.
- **Duplicate Prevention:** Uses a local SQLite database (`posted_articles.db`) to remember posted articles.
- **Categorization:** News is color-coded by category:
  - 🟡 Enterprise & Industry Strategy (Gold)
  - 🔵 Tech & Innovation (Blue)
  - 🔬 Research & Development (Red)
  - 🛠️ Cloud & Infrastructure (Teal)

## Customization

Edit `src/config.py` to add/remove RSS feeds or change categories.

Clear the database (optional) to force it to repost everything:

```bash
rm data/posted_articles.db
```

### Check feed health

Run the feed checker to see which RSS URLs are reachable before starting the bot:

```bash
python scripts/check_feeds.py
```

---

## Deploying to a VPS (Hostinger / Ubuntu + Docker)

The bot runs as a Docker container — no web server or Traefik config needed, just Docker Compose.

### 1. SSH into the server

```bash
ssh root@<your-server-ip>
```

### 2. Clone the repository

```bash
git clone https://github.com/canderson777/ainewsbot.git
cd ainewsbot
```

### 3. Create your `.env` file

```bash
cp .env.example .env
nano .env   # fill in DISCORD_TOKEN and DISCORD_CHANNEL_ID
```

### 4. Start the bot

```bash
docker compose up -d --build
```

The `--build` flag is only needed the first time or after code changes. For subsequent starts use `docker compose up -d`.

### 5. Check the logs

```bash
docker compose logs -f
```

You should see the bot log in and the background task start. Use `!force_news` in Discord to trigger an immediate post.

### Useful commands

| Command | Purpose |
|---|---|
| `docker compose up -d --build` | Build and start (detached) |
| `docker compose logs -f` | Stream live logs |
| `docker compose restart` | Restart the bot |
| `docker compose down` | Stop and remove container |
| `docker compose pull && docker compose up -d --build` | Deploy latest code after a `git pull` |

The `data/` directory on the host holds the SQLite database and persists across restarts and rebuilds. The bot auto-restarts if it crashes or if the server reboots (`restart: unless-stopped`).

