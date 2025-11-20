import os
import discord
import asyncio
from discord.ext import commands, tasks
from dotenv import load_dotenv
from src.config import UPDATE_INTERVAL_HOURS
from src.database import init_db, add_article
from src.feed_fetcher import fetch_all_feeds

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

        for article in articles:
            embed = discord.Embed(
                title=article['title'],
                url=article['link'],
                description=article['summary'],
                color=article['color']
            )
            embed.set_author(name=article['category'])
            if article['published']:
                embed.set_footer(text=f"Published: {article['published']}")
            
            try:
                await target_channel.send(embed=embed)
                add_article(article['link'], article['title'], article['published'])
                # Sleep briefly to avoid rate limits
                await asyncio.sleep(1)
            except Exception as e:
                print(f"Failed to send article {article['link']}: {e}")
                
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

