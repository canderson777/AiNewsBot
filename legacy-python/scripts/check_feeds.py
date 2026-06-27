#!/usr/bin/env python3
"""
Feed health checker — run this to verify all configured RSS feeds are reachable.

Uses only stdlib + requests (no feedparser required) so it can be run at any time.

Usage:
    python scripts/check_feeds.py
"""
import sys
import urllib.request
import urllib.error
import xml.etree.ElementTree as ET

FEEDS = {
    "Enterprise & Industry Strategy": [
        "https://www.fastcompany.com/section/artificial-intelligence/rss",
        "https://venturebeat.com/category/ai/feed/",
        "https://news.crunchbase.com/tag/artificial-intelligence/feed/",
        "https://news.microsoft.com/source/topic/ai/feed/",
    ],
    "Tech & Innovation": [
        "https://www.wired.com/feed/category/artificial-intelligence/latest/rss",
        "https://techcrunch.com/category/artificial-intelligence/feed/",
        "https://www.geekwire.com/tag/ai/feed/",
        "https://rss.nytimes.com/services/xml/rss/nyt/ArtificialIntelligence.xml",
        "https://www.theverge.com/rss/index.xml",
        "https://arstechnica.com/ai/feed/",
        "https://www.theguardian.com/technology/artificialintelligence/rss",
    ],
    "Research & Development": [
        "https://openai.com/news/rss.xml",
        "https://blog.google/technology/google-deepmind/rss/",
        "https://www.technologyreview.com/feed/",
        "https://spectrum.ieee.org/feeds/topic/artificial-intelligence.rss",
    ],
    "Cloud & Infrastructure": [
        "https://aws.amazon.com/blogs/machine-learning/feed/",
    ],
}

HEADERS = {"User-Agent": "Mozilla/5.0 (compatible; AiNewsBot/1.0; feed-checker)"}
TIMEOUT = 15


def check_feed(url):
    """Return (ok, item_count, feed_title, error_msg)."""
    try:
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=TIMEOUT) as resp:
            status = resp.status
            if status not in (200, 301, 302):
                return False, 0, "", f"HTTP {status}"
            raw = resp.read()
    except urllib.error.HTTPError as e:
        return False, 0, "", f"HTTP {e.code}"
    except urllib.error.URLError as e:
        return False, 0, "", f"Network error: {e.reason}"
    except Exception as e:
        return False, 0, "", str(e)

    try:
        root = ET.fromstring(raw)
        # Handle both RSS (<channel><title>) and Atom (<feed><title>) formats
        ns = {"atom": "http://www.w3.org/2005/Atom"}
        title_el = (
            root.find("channel/title")
            or root.find("atom:title", ns)
            or root.find("{http://www.w3.org/2005/Atom}title")
        )
        title = title_el.text.strip() if title_el is not None and title_el.text else "(no title)"

        items = root.findall(".//item") or root.findall(
            ".//{http://www.w3.org/2005/Atom}entry"
        )
        return True, len(items), title, ""
    except ET.ParseError as e:
        return False, 0, "", f"XML parse error: {e}"


def main():
    ok_count = 0
    fail_count = 0

    for category, urls in FEEDS.items():
        print(f"\n{'='*62}")
        print(f"  {category}")
        print(f"{'='*62}")
        for url in urls:
            ok, count, title, err = check_feed(url)
            if ok:
                print(f"  [OK]   {count:>3} items   {title}")
                print(f"         {url}")
                ok_count += 1
            else:
                print(f"  [FAIL] {err}")
                print(f"         {url}")
                fail_count += 1

    print(f"\n{'='*62}")
    print(f"  Results: {ok_count} OK, {fail_count} FAILED")
    print(f"{'='*62}\n")

    if fail_count:
        sys.exit(1)


if __name__ == "__main__":
    main()
