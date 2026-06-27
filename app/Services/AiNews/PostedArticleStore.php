<?php

namespace App\Services\AiNews;

use Illuminate\Support\Collection;

class PostedArticleStore
{
    public function postedLinks(): array
    {
        return collect($this->read()['articles'] ?? [])
            ->pluck('link')
            ->filter()
            ->values()
            ->all();
    }

    public function remember(Collection $articles): void
    {
        $this->withLock(function (array $data) use ($articles): array {
            $posted = collect($data['articles'] ?? [])
                ->keyBy('link_hash');

            foreach ($articles as $article) {
                $posted->put(hash('sha256', $article['link']), [
                    'link_hash' => hash('sha256', $article['link']),
                    'link' => $article['link'],
                    'title' => $article['title'],
                    'source' => $article['source'],
                    'category' => $article['category'],
                    'published_at' => optional($article['published_at'])->toIso8601String(),
                    'posted_at' => now()->toIso8601String(),
                ]);
            }

            return [
                'articles' => $posted
                    ->sortByDesc('posted_at')
                    ->take((int) config('ai-news.history_limit', 1000))
                    ->values()
                    ->all(),
            ];
        });
    }

    private function read(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return ['articles' => []];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : ['articles' => []];
    }

    private function withLock(callable $callback): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open posted article store: {$path}");
        }

        try {
            flock($handle, LOCK_EX);

            $contents = stream_get_contents($handle);
            $data = $contents ? json_decode($contents, true) : ['articles' => []];
            $data = is_array($data) ? $data : ['articles' => []];

            $updated = $callback($data);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function path(): string
    {
        return config('ai-news.history_path') ?: storage_path('app/ai-news/posted-articles.json');
    }
}
