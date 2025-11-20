import sqlite3
import os
from src.config import DB_PATH

def get_db_connection():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    """Initialize the database with the necessary tables."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS posted_articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            link TEXT UNIQUE NOT NULL,
            title TEXT,
            published_at TEXT
        )
    ''')
    conn.commit()
    conn.close()

def is_article_posted(link):
    """Check if an article has already been posted."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('SELECT 1 FROM posted_articles WHERE link = ?', (link,))
    result = cursor.fetchone()
    conn.close()
    return result is not None

def add_article(link, title, published_at):
    """Add a posted article to the database."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute('INSERT INTO posted_articles (link, title, published_at) VALUES (?, ?, ?)', 
                       (link, title, published_at))
        conn.commit()
    except sqlite3.IntegrityError:
        # Already exists
        pass
    finally:
        conn.close()

