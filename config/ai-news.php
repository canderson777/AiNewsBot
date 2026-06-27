<?php

return [
    'discord' => [
        'bot_token' => env('DISCORD_TOKEN'),
        'channel_id' => env('DISCORD_CHANNEL_ID'),
    ],

    'schedule_timezone' => env('AI_NEWS_SCHEDULE_TIMEZONE', 'America/New_York'),
    'max_age_days' => (int) env('AI_NEWS_MAX_AGE_DAYS', 7),
    'max_per_category' => (int) env('AI_NEWS_MAX_PER_CATEGORY', 5),
    'max_total_articles' => (int) env('AI_NEWS_MAX_TOTAL_ARTICLES', 15),
    'max_embed_pages' => (int) env('AI_NEWS_MAX_EMBED_PAGES', 3),
    'history_path' => env('AI_NEWS_HISTORY_PATH'),
    'history_limit' => (int) env('AI_NEWS_HISTORY_LIMIT', 1000),

    'categories' => [
        'Enterprise & Industry Strategy' => [
            'emoji' => '🟡',
            'color' => 0xf1c40f,
            'feeds' => [
                'https://www.fastcompany.com/section/artificial-intelligence/rss',
                'https://venturebeat.com/category/ai/feed/',
                'https://news.crunchbase.com/tag/artificial-intelligence/feed/',
                'https://news.microsoft.com/source/topic/ai/feed/',
            ],
        ],
        'Tech & Innovation' => [
            'emoji' => '🔵',
            'color' => 0x3498db,
            'feeds' => [
                'https://www.wired.com/feed/category/artificial-intelligence/latest/rss',
                'https://techcrunch.com/category/artificial-intelligence/feed/',
                'https://www.geekwire.com/tag/ai/feed/',
                'https://rss.nytimes.com/services/xml/rss/nyt/ArtificialIntelligence.xml',
                'https://www.theverge.com/rss/index.xml',
                'https://arstechnica.com/ai/feed/',
                'https://www.theguardian.com/technology/artificialintelligence/rss',
            ],
        ],
        'Research & Development' => [
            'emoji' => '🔬',
            'color' => 0xe74c3c,
            'feeds' => [
                'https://openai.com/news/rss.xml',
                'https://blog.google/technology/google-deepmind/rss/',
                'https://www.technologyreview.com/feed/',
                'https://spectrum.ieee.org/feeds/topic/artificial-intelligence.rss',
            ],
        ],
        'Cloud & Infrastructure' => [
            'emoji' => '🛠️',
            'color' => 0x1abc9c,
            'feeds' => [
                'https://aws.amazon.com/blogs/machine-learning/feed/',
            ],
        ],
    ],
];
