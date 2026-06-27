<?php

namespace App\Services\AiNews;

use App\Models\PostedArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiNewsPoster
{
    public function __construct(
        private readonly FeedFetcher $feeds,
        private readonly DiscordMessageBuilder $messages,
        private readonly DiscordClient $discord,
    ) {
    }

    public function post(bool $dryRun = false): array
    {
        $articles = $this->selectTopArticles($this->feeds->fetchFreshArticles());

        if ($articles->isEmpty()) {
            return ['fetched' => 0, 'selected' => 0, 'embeds' => 0, 'posted' => false];
        }

        $embeds = $this->messages->buildEmbeds($articles);

        if ($embeds === []) {
            return ['fetched' => $articles->count(), 'selected' => 0, 'embeds' => 0, 'posted' => false];
        }

        if (! $dryRun) {
            $this->discord->sendEmbeds($embeds);
            $this->markPosted($articles);
        }

        Log::info('AI news digest processed', [
            'selected' => $articles->count(),
            'embeds' => count($embeds),
            'dry_run' => $dryRun,
        ]);

        return [
            'fetched' => $articles->count(),
            'selected' => $articles->count(),
            'embeds' => count($embeds),
            'posted' => ! $dryRun,
        ];
    }

    private function selectTopArticles(Collection $articles): Collection
    {
        $perCategory = [];
        $selected = collect();

        foreach ($articles->sortByDesc('published_at') as $article) {
            $category = $article['category'];

            if (($perCategory[$category] ?? 0) >= config('ai-news.max_per_category')) {
                continue;
            }

            $selected->push($article);
            $perCategory[$category] = ($perCategory[$category] ?? 0) + 1;

            if ($selected->count() >= config('ai-news.max_total_articles')) {
                break;
            }
        }

        return $selected->values();
    }

    private function markPosted(Collection $articles): void
    {
        DB::transaction(function () use ($articles): void {
            foreach ($articles as $article) {
                PostedArticle::query()->firstOrCreate(
                    ['link_hash' => hash('sha256', $article['link'])],
                    [
                        'link' => $article['link'],
                        'title' => $article['title'],
                        'source' => $article['source'],
                        'category' => $article['category'],
                        'published_at' => $article['published_at'],
                    ],
                );
            }
        });
    }
}
