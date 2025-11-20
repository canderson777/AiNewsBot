import discord

# Configuration for RSS Feeds and Categories

CATEGORIES = {
    "Business & Industry News": {
        "color": discord.Color.gold(),
        "feeds": [
            "https://spectrum.ieee.org/feeds/topic/artificial-intelligence.rss",
            "https://www.fastcompany.com/section/artificial-intelligence/rss",
            "https://venturebeat.com/category/ai/feed/",
            "https://news.crunchbase.com/sections/artificial-intelligence/feed/",
            "https://aws.amazon.com/blogs/machine-learning/feed/",
            "https://news.microsoft.com/source/topic/ai/feed/"
        ]
    },
    "General & Broad News": {
        "color": discord.Color.blue(),
        "feeds": [
            "https://www.technologyreview.com/feed/topic/artificial-intelligence",
            "https://www.wired.com/feed/tag/ai/latest/rss",
            "https://techcrunch.com/category/artificial-intelligence/feed/",
            "https://www.geekwire.com/tag/ai/feed/",
            "https://rss.nytimes.com/services/xml/rss/nyt/ArtificialIntelligence.xml",
            "https://www.theverge.com/rss/ai-artificial-intelligence/index.xml",
            "https://arstechnica.com/ai/feed/",
            "https://www.theguardian.com/technology/artificialintelligence/rss",
            # "https://rss.beehiiv.com/feeds/2R3C6B..." # TODO: Verify full URL for The Rundown
        ]
    },
    "Research & Technical News": {
        "color": discord.Color.red(),
        "feeds": [
            "https://openai.com/news/rss.xml",
            "https://blog.google/technology/google-deepmind/rss/"
        ]
    }
}

# Update interval in hours
UPDATE_INTERVAL_HOURS = 8

# Database path
DB_PATH = "posted_articles.db"

