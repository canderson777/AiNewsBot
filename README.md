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

Clear the database (optional) to force it to repost everything with the new format:
Remove-Item posted_articles.db
python -m src.main

