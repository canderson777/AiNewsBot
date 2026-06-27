<?php

namespace App\Services\AiNews;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SimplePie\SimplePie;

class FeedFetcher
{
    public function __construct(
        private ?PostedArticleStore $postedArticles = null,
    ) {
    }

    public function fetchFreshArticles(): Collection
    {
        $postedLinks = $this->postedArticles()->postedLinks();
        $postedLinks = array_fill_keys($postedLinks, true);
        $seenLinks = [];
        $articles = collect();
        $cutoff = now()->subDays(config('ai-news.max_age_days'))->toImmutable();

        foreach (config('ai-news.categories') as $category => $settings) {
            foreach ($settings['feeds'] as $url) {
                foreach ($this->parseFeed($url, $category, $settings, $cutoff) as $article) {
                    if (isset($postedLinks[$article['link']]) || isset($seenLinks[$article['link']])) {
                        continue;
                    }

                    $seenLinks[$article['link']] = true;
                    $articles->push($article);
                }
            }
        }

        return $articles;
    }

    private function postedArticles(): PostedArticleStore
    {
        return $this->postedArticles ??= app(PostedArticleStore::class);
    }

    private function parseFeed(string $url, string $category, array $settings, CarbonImmutable $cutoff): array
    {
        $feed = new SimplePie();
        $feed->set_feed_url($url);
        $feed->enable_cache(false);
        $feed->init();

        if ($feed->error()) {
            Log::warning('RSS feed failed', ['url' => $url, 'error' => $feed->error()]);

            return [];
        }

        $source = $this->shortSourceName($feed->get_title() ?: 'Unknown Source');
        $articles = [];

        foreach (array_slice($feed->get_items(), 0, 10) as $item) {
            $link = $item->get_permalink();
            $publishedAt = $item->get_date('U');

            if (! $link || ! $publishedAt) {
                continue;
            }

            $publishedAt = CarbonImmutable::createFromTimestamp((int) $publishedAt);

            if ($publishedAt->lessThan($cutoff)) {
                continue;
            }

            $articles[] = [
                'title' => html_entity_decode($item->get_title() ?: 'Untitled', ENT_QUOTES | ENT_HTML5),
                'link' => $link,
                'summary' => $this->summary($item->get_description() ?: ''),
                'published_at' => $publishedAt,
                'formatted_date' => $publishedAt->format('g:ia m/d'),
                'source' => $source,
                'category' => $category,
                'emoji' => $settings['emoji'] ?? '*',
                'color' => $settings['color'] ?? 0x2f3136,
            ];
        }

        return $articles;
    }

    private function shortSourceName(string $source): string
    {
        return match (true) {
            str_contains($source, 'Wired') => 'Wired',
            str_contains($source, 'Technology Review') => 'MIT Tech Review',
            str_contains($source, 'TechCrunch') => 'TechCrunch',
            str_contains($source, 'New York Times') => 'NYT',
            default => $source,
        };
    }

    private function summary(string $html): string
    {
        $summary = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return mb_strlen($summary) > 150
            ? mb_substr($summary, 0, 147).'...'
            : $summary;
    }
}
