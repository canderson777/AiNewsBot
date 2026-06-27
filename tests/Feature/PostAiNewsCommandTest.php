<?php

namespace Tests\Feature;

use App\Services\AiNews\FeedFetcher;
use App\Services\AiNews\PostedArticleStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostAiNewsCommandTest extends TestCase
{
    public function test_it_posts_a_digest_and_marks_articles_as_posted(): void
    {
        $historyPath = storage_path('framework/testing/posted-articles.json');

        config([
            'ai-news.discord.bot_token' => 'test-token',
            'ai-news.discord.channel_id' => '123456',
            'ai-news.history_path' => $historyPath,
        ]);

        @unlink($historyPath);

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
                        'emoji' => '*',
                        'color' => 0xe74c3c,
                    ],
                ]);
            }
        });

        $this->artisan('news:post')
            ->assertSuccessful();

        $this->assertContains(
            'https://example.com/openai-news',
            app(PostedArticleStore::class)->postedLinks(),
        );

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.com/api/v10/channels/123456/messages'
            && $request['embeds'][0]['fields'][0]['value'] !== '');
    }

    public function test_dry_run_builds_without_posting_or_marking_articles(): void
    {
        $historyPath = storage_path('framework/testing/posted-articles-dry-run.json');

        config([
            'ai-news.history_path' => $historyPath,
        ]);

        @unlink($historyPath);

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
                        'emoji' => '*',
                        'color' => 0x1abc9c,
                    ],
                ]);
            }
        });

        $this->artisan('news:post --dry-run')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($historyPath);
    }
}
