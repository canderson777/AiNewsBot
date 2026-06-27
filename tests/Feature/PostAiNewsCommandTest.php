<?php

namespace Tests\Feature;

use App\Services\AiNews\FeedFetcher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostAiNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_posts_a_digest_and_marks_articles_as_posted(): void
    {
        config([
            'ai-news.discord.bot_token' => 'test-token',
            'ai-news.discord.channel_id' => '123456',
        ]);

        Http::fake([
            'discord.com/*' => Http::response(['id' => 'message-1'], 200),
        ]);

        $this->app->instance(FeedFetcher::class, new class extends FeedFetcher
        {
            public function fetchFreshArticles(): Collection
            {
                return collect([
                    [
                        'title' => 'OpenAI Ships Something Useful',
                        'link' => 'https://example.com/openai-news',
                        'summary' => 'A practical update for AI teams.',
                        'published_at' => CarbonImmutable::now(),
                        'formatted_date' => CarbonImmutable::now()->format('g:ia m/d'),
                        'source' => 'Example Source',
                        'category' => 'Research & Development',
                        'emoji' => '🔬',
                        'color' => 0xe74c3c,
                    ],
                ]);
            }
        });

        $this->artisan('news:post')
            ->assertSuccessful();

        $this->assertDatabaseHas('posted_articles', [
            'link_hash' => hash('sha256', 'https://example.com/openai-news'),
            'title' => 'OpenAI Ships Something Useful',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.com/api/v10/channels/123456/messages'
            && $request['embeds'][0]['fields'][0]['name'] === '🔬 __**RESEARCH & DEVELOPMENT**__');
    }

    public function test_dry_run_builds_without_posting_or_marking_articles(): void
    {
        $this->app->instance(FeedFetcher::class, new class extends FeedFetcher
        {
            public function fetchFreshArticles(): Collection
            {
                return collect([
                    [
                        'title' => 'Cloud AI Update',
                        'link' => 'https://example.com/cloud-ai',
                        'summary' => 'Infrastructure news.',
                        'published_at' => CarbonImmutable::now(),
                        'formatted_date' => CarbonImmutable::now()->format('g:ia m/d'),
                        'source' => 'Example Source',
                        'category' => 'Cloud & Infrastructure',
                        'emoji' => '🛠️',
                        'color' => 0x1abc9c,
                    ],
                ]);
            }
        });

        $this->artisan('news:post --dry-run')
            ->assertSuccessful();

        $this->assertDatabaseMissing('posted_articles', [
            'link_hash' => hash('sha256', 'https://example.com/cloud-ai'),
        ]);
    }
}
