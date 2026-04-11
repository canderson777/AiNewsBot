# AI News Bot

A Discord bot that fetches AI news from RSS feeds and posts a categorized daily summary to a channel.

## Run Locally

1. Install Python 3.8+
2. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
3. Copy `.env.example` to `.env` and fill in:
   - `DISCORD_TOKEN` — from https://discord.com/developers/applications
   - `DISCORD_CHANNEL_ID` — enable Developer Mode in Discord, right-click the channel → "Copy ID"
4. Run the bot:
   ```bash
   python -m src.main
   ```
5. In Discord, type `!force_news` to trigger an immediate post.

## Set Up on VPS (Ubuntu + Docker)

1. SSH into the server:
   ```bash
   ssh root@<your-server-ip>
   ```
2. Clone the repo:
   ```bash
   git clone https://github.com/canderson777/ainewsbot.git
   cd ainewsbot
   ```
3. Create `.env`:
   ```bash
   cp .env.example .env
   nano .env   # fill in DISCORD_TOKEN and DISCORD_CHANNEL_ID
   ```
4. Start the container:
   ```bash
   docker compose up -d --build
   ```
5. Check logs:
   ```bash
   docker compose logs -f
   ```

### Deploying code changes

```bash
cd ~/ainewsbot
git pull
docker compose up -d --build
```

### Useful commands

| Command | Purpose |
|---|---|
| `docker compose up -d --build` | Build and start (detached) |
| `docker compose logs -f` | Stream live logs |
| `docker compose restart` | Restart the bot |
| `docker compose down` | Stop and remove container |

The `data/` directory persists the SQLite dedupe database across restarts. The container auto-restarts on crash or reboot (`restart: unless-stopped`).

## Bot Defaults

Configured in [src/config.py](src/config.py) and [src/main.py](src/main.py):

| Setting | Default | Description |
|---|---|---|
| `UPDATE_INTERVAL_HOURS` | `12` | How often the bot checks feeds |
| `MAX_AGE_DAYS` | `7` | Articles older than this are skipped |
| `MAX_PER_CATEGORY` | `5` | Cap on articles posted per category |
| `MAX_TOTAL_ARTICLES` | `15` | Cap on total articles per post |
| `MAX_EMBED_PAGES` | `3` | Cap on Discord embed pages per post |
| `DB_PATH` | `data/posted_articles.db` | SQLite dedupe database path |

Command: `!force_news` — manually triggers a fetch-and-post cycle.

## Sources

News is grouped into four color-coded categories:

### 🟡 Enterprise & Industry Strategy
- Fast Company — AI section
- VentureBeat — AI
- Crunchbase News — AI tag
- Microsoft News — AI topic

### 🔵 Tech & Innovation
- Wired — AI
- TechCrunch — AI
- GeekWire — AI tag
- New York Times — AI
- The Verge
- Ars Technica — AI
- The Guardian — AI

### 🔬 Research & Development
- OpenAI News
- Google DeepMind Blog
- MIT Technology Review
- IEEE Spectrum — AI

### 🛠️ Cloud & Infrastructure
- AWS Machine Learning Blog

Edit [src/config.py](src/config.py) to add or remove feeds.

## Troubleshooting

- **Reset the dedupe database** (forces the bot to repost everything within the age window):
  ```bash
  rm data/posted_articles.db
  ```
- **Check feed health** before starting the bot:
  ```bash
  python scripts/check_feeds.py
  ```
