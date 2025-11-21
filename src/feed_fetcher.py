import feedparser
import re
import asyncio
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
from time import mktime
from src.config import CATEGORIES
from src.database import is_article_posted

def clean_html(raw_html):
    """Remove HTML tags from string."""
    cleanr = re.compile('<.*?>')
    cleantext = re.sub(cleanr, '', raw_html)
    return cleantext.strip()

def parse_date(entry):
    """Parse the published date from the entry."""
    try:
        if hasattr(entry, 'published_parsed') and entry.published_parsed:
            dt = datetime.fromtimestamp(mktime(entry.published_parsed))
            return dt.strftime("%I:%M%p %m/%d").lower().lstrip("0")
        elif hasattr(entry, 'updated_parsed') and entry.updated_parsed:
             dt = datetime.fromtimestamp(mktime(entry.updated_parsed))
             return dt.strftime("%I:%M%p %m/%d").lower().lstrip("0")
        return ""
    except Exception:
        return ""

def parse_feed(url, category_name, category_color):
    """Parse a single feed and return new articles."""
    try:
        feed = feedparser.parse(url)
        feed_title = feed.feed.get('title', 'Unknown Source')
        
        # Shorten common long feed titles for cleaner display
        if "Wired" in feed_title: feed_title = "Wired"
        elif "Technology Review" in feed_title: feed_title = "MIT Tech Review"
        elif "TechCrunch" in feed_title: feed_title = "TechCrunch"
        elif "New York Times" in feed_title: feed_title = "NYT"
        
        new_articles = []
        
        # Check only the latest 10 entries to avoid spamming on first run if DB is empty
        # or to keep performance high
        for entry in feed.entries[:10]:
            link = entry.link
            
            if is_article_posted(link):
                continue
                
            title = entry.title
            # Prefer summary, fallback to description, then empty string
            summary = getattr(entry, 'summary', '')
            if not summary:
                summary = getattr(entry, 'description', '')
            
            # Clean up summary
            summary = clean_html(summary)
            # Truncate if too long, keeping it shorter for list format
            if len(summary) > 150:
                summary = summary[:147] + "..."
                
            published = getattr(entry, 'published', '')
            formatted_date = parse_date(entry)
            
            new_articles.append({
                'title': title,
                'link': link,
                'summary': summary,
                'published': published,
                'formatted_date': formatted_date,
                'source': feed_title,
                'category': category_name,
                'color': category_color
            })
            
        return new_articles
    except Exception as e:
        print(f"Error parsing feed {url}: {e}")
        return []

async def fetch_all_feeds():
    """Fetch all feeds in parallel using a thread pool."""
    loop = asyncio.get_running_loop()
    tasks = []
    
    with ThreadPoolExecutor(max_workers=10) as executor:
        for category_name, data in CATEGORIES.items():
            color = data['color']
            for url in data['feeds']:
                # Schedule the synchronous parse_feed function to run in the executor
                task = loop.run_in_executor(executor, parse_feed, url, category_name, color)
                tasks.append(task)
        
        # Wait for all tasks to complete
        results = await asyncio.gather(*tasks)
        
    # Flatten the list of lists
    flat_results = [item for sublist in results for item in sublist]
    
    # Remove duplicates based on link (in case multiple feeds have same article)
    unique_results = []
    seen_links = set()
    for article in flat_results:
        if article['link'] not in seen_links:
            seen_links.add(article['link'])
            unique_results.append(article)
            
    return unique_results
