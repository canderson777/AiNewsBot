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
python src/main.py
```

## Features

- **Automatic Updates:** Checks for news every 8 hours (configurable in `src/config.py`).
- **Manual Trigger:** Use `!force_news` in any channel the bot has access to (or the news channel) to force a check immediately.
- **Duplicate Prevention:** Uses a local SQLite database (`posted_articles.db`) to remember posted articles.
- **Categorization:** News is color-coded by category:
  - Business & Industry (Gold)
  - General & Broad (Blue)
  - Research & Technical (Red)

## Customization

Edit `src/config.py` to add/remove RSS feeds or change categories.

