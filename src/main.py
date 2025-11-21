import os
import discord
import asyncio
from discord.ext import commands, tasks
from dotenv import load_dotenv
from datetime import datetime

from src.config import UPDATE_INTERVAL_HOURS, CATEGORIES
from src.database import init_db, add_article
from src.feed_fetcher import fetch_all_feeds

FIELD_CHAR_LIMIT = 1024
EMBED_CHAR_LIMIT = 5500
CONTINUATION_FIELD_NAME = "\u200B"

# Load environment variables
load_dotenv()

TOKEN = os.getenv('DISCORD_TOKEN')
CHANNEL_ID = os.getenv('DISCORD_CHANNEL_ID')

# Validate Environment Variables
if not TOKEN or not CHANNEL_ID:
    print("Error: DISCORD_TOKEN or DISCORD_CHANNEL_ID not found in .env file.")
    print("Please check your .env file.")
    # We don't exit here to allow the user to fix it without restarting the script immediately if they are running iteratively, 
    # but realistically the bot won't start. 
    # For this script, we'll just warn.

intents = discord.Intents.default()
intents.message_content = True # Required for commands if we use them

bot = commands.Bot(command_prefix='!', intents=intents)


def _format_article_entry(article: dict) -> str:
    """Return the bullet list entry for an article."""
    summary = article['summary'] or ''
    summary = summary[:120] + ("..." if len(summary) > 120 else "")
    
    # Add Source and Time info
    # Example: Wired Magazine 8:00am 11/21
    source_info = f"{article.get('source', 'Unknown Source')}"
    if article.get('formatted_date'):
        source_info += f" {article['formatted_date']}"
    
    if summary:
        content = f"{summary}\n_{source_info}_"
    else:
        content = f"_{source_info}_"
        
    return f"• **[{article['title']}]({article['link']})**\n{content}\n\n"


def _chunk_category_articles(category: str, articles: list[dict]) -> list[dict]:
    """Split category articles into multiple fields capped at Discord's limit."""
    if not articles:
        return []

    emoji = CATEGORIES.get(category, {}).get("emoji", "•")
    heading = f"{emoji} __**{category.upper()}**__\n"

    fields = []
    current_value = ""
    field_name = heading

    for article in articles:
        entry = _format_article_entry(article)

        if current_value and len(current_value) + len(entry) > FIELD_CHAR_LIMIT:
            fields.append({"name": field_name, "value": current_value.strip() + "\n"})
            current_value = entry
            field_name = CONTINUATION_FIELD_NAME
        else:
            current_value += entry

    if current_value:
        fields.append({"name": field_name, "value": current_value.strip() + "\n"})

    return fields


def _build_category_fields(articles_by_category: dict) -> list[dict]:
    """Create a flattened list of embed fields from grouped articles."""
    fields: list[dict] = []
    for category, articles in articles_by_category.items():
        fields.extend(_chunk_category_articles(category, articles))
    return fields


def _base_embed(date_str: str, page: int) -> discord.Embed:
    """Create a base embed with consistent styling."""
    if page == 1:
        title = f"📰 Daily AI News Summary - {date_str}"
        description = "Here are the latest updates from the world of AI."
    else:
        title = f"📰 Daily AI News Summary - {date_str} (Page {page})"
        description = "Additional stories continue below."

    return discord.Embed(title=title, description=description, color=discord.Color.dark_theme())


def _paginate_embeds(fields: list[dict], date_str: str) -> list[discord.Embed]:
    """Split fields across multiple embeds if we near Discord's 6000 char limit."""
    embeds: list[discord.Embed] = []
    page = 1
    current_embed = _base_embed(date_str, page)
    current_length = len(current_embed.title) + len(current_embed.description)

    for field in fields:
        field_length = len(field['name']) + len(field['value'])

        # Start a new embed if adding this field would exceed the limit
        if current_embed.fields and current_length + field_length > EMBED_CHAR_LIMIT:
            embeds.append(current_embed)
            page += 1
            current_embed = _base_embed(date_str, page)
            current_length = len(current_embed.title) + len(current_embed.description)

        current_embed.add_field(name=field['name'], value=field['value'], inline=False)
        current_length += field_length

    if current_embed.fields:
        embeds.append(current_embed)

    return embeds

@bot.event
async def on_ready():
    print(f'Logged in as {bot.user} (ID: {bot.user.id})')
    print('------')
    
    # Initialize database
    init_db()
    
    # Start the background task if not already running
    if not feed_update_task.is_running():
        feed_update_task.start()

@tasks.loop(hours=UPDATE_INTERVAL_HOURS)
async def feed_update_task():
    """Background task to fetch and post news."""
    await process_news()

async def process_news(ctx=None):
    """Fetch news and post to the configured channel."""
    print("Checking for new articles...")
    
    if ctx:
        await ctx.send("Checking for new articles...")
        
    try:
        channel_id = int(CHANNEL_ID)
        channel = bot.get_channel(channel_id)
        
        if not channel and not ctx:
            print(f"Error: Could not find channel with ID {CHANNEL_ID}")
            return
        
        target_channel = channel if channel else ctx.channel
        
        articles = await fetch_all_feeds()
        
        if not articles:
            print("No new articles found.")
            if ctx:
                await ctx.send("No new articles found.")
            return
            
        print(f"Found {len(articles)} new articles. Posting...")
        if ctx:
            await ctx.send(f"Found {len(articles)} new articles. Posting...")

        # Group articles by category for chunking/pagination
        articles_by_category = {category: [] for category in CATEGORIES.keys()}
        for article in articles:
            if article['category'] in articles_by_category:
                articles_by_category[article['category']].append(article)

        # Build embed fields and paginate if needed
        fields = _build_category_fields(articles_by_category)
        if not fields:
            print("No category fields could be built.")
            if ctx:
                await ctx.send("No category fields available to share.")
            return
        
        date_str = datetime.now().strftime('%Y-%m-%d')
        embeds_to_send = _paginate_embeds(fields, date_str)

        for embed in embeds_to_send:
            await target_channel.send(embed=embed)

        # Mark all articles as posted
        for article in articles:
            add_article(article['link'], article['title'], article['published'])
                
    except Exception as e:
        print(f"Error in process_news: {e}")
        if ctx:
            await ctx.send(f"Error occurred: {e}")

@feed_update_task.before_loop
async def before_feed_update():
    """Wait until the bot is ready before starting the loop."""
    await bot.wait_until_ready()

@bot.command(name='force_news')
async def force_news(ctx):
    """Manually trigger the news fetch."""
    await process_news(ctx)

if __name__ == "__main__":
    if TOKEN:
        bot.run(TOKEN)
    else:
        print("Please configure your .env file with a valid DISCORD_TOKEN.")
