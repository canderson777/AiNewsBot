import os
import discord

# Configuration for RSS Feeds and Categories

CATEGORIES = {
    "Enterprise & Industry Strategy": {
        "color": discord.Color.gold(),
        "emoji": "🟡",
        "feeds": [
            "https://www.fastcompany.com/section/artificial-intelligence/rss",
            "https://venturebeat.com/category/ai/feed/",
            "https://news.crunchbase.com/tag/artificial-intelligence/feed/",
            "https://news.microsoft.com/source/topic/ai/feed/"
        ]
    },
    "Tech & Innovation": {
        "color": discord.Color.blue(),
        "emoji": "🔵",
        "feeds": [
            "https://www.wired.com/feed/category/artificial-intelligence/latest/rss",
            "https://techcrunch.com/category/artificial-intelligence/feed/",
            "https://www.geekwire.com/tag/ai/feed/",
            "https://rss.nytimes.com/services/xml/rss/nyt/ArtificialIntelligence.xml",
            "https://www.theverge.com/rss/index.xml",
            "https://arstechnica.com/ai/feed/",
            "https://www.theguardian.com/technology/artificialintelligence/rss"
        ]
    },
    "Research & Development": {
        "color": discord.Color.red(),
        "emoji": "🔬",
        "feeds": [
            "https://openai.com/news/rss.xml",
            "https://blog.google/technology/google-deepmind/rss/",
            "https://www.technologyreview.com/feed/",
            "https://spectrum.ieee.org/feeds/topic/artificial-intelligence.rss"
        ]
    },
    "Cloud & Infrastructure": {
        "color": discord.Color.teal(),
        "emoji": "🛠️",
        "feeds": [
            "https://aws.amazon.com/blogs/machine-learning/feed/"
        ]
    }
}

# Update interval in hours
UPDATE_INTERVAL_HOURS = 12

# Database path — uses data/ subdir so Docker volume mounts persist the DB
DB_PATH = os.environ.get("DB_PATH", "data/posted_articles.db")

