<?php

namespace App\Services\AiNews;

use Illuminate\Support\Collection;

class DiscordMessageBuilder
{
    private const FIELD_CHAR_LIMIT = 1024;
    private const EMBED_CHAR_LIMIT = 5500;
    private const CONTINUATION_FIELD_NAME = "\u{200B}";

    public function buildEmbeds(Collection $articles): array
    {
        $fields = collect(config('ai-news.categories'))
            ->keys()
            ->flatMap(fn (string $category) => $this->categoryFields($category, $articles->where('category', $category)->values()))
            ->values();

        if ($fields->isEmpty()) {
            return [];
        }

        return $this->paginateFields($fields);
    }

    private function categoryFields(string $category, Collection $articles): array
    {
        if ($articles->isEmpty()) {
            return [];
        }

        $emoji = config("ai-news.categories.$category.emoji", '*');
        $fieldName = "{$emoji} __**".mb_strtoupper($category).'**__';
        $current = '';
        $fields = [];

        foreach ($articles as $article) {
            $entry = $this->articleEntry($article);

            if ($current !== '' && mb_strlen($current.$entry) > self::FIELD_CHAR_LIMIT) {
                $fields[] = ['name' => $fieldName, 'value' => trim($current)."\n", 'inline' => false];
                $current = $entry;
                $fieldName = self::CONTINUATION_FIELD_NAME;

                continue;
            }

            $current .= $entry;
        }

        if ($current !== '') {
            $fields[] = ['name' => $fieldName, 'value' => trim($current)."\n", 'inline' => false];
        }

        return $fields;
    }

    private function articleEntry(array $article): string
    {
        $summary = $article['summary'] ? mb_substr($article['summary'], 0, 120) : '';
        $summary .= mb_strlen($article['summary'] ?? '') > 120 ? '...' : '';

        $source = $article['source'] ?? 'Unknown Source';
        $date = $article['formatted_date'] ?? null;
        $sourceLine = trim($source.' '.$date);
        $content = $summary !== '' ? "{$summary}\n_{$sourceLine}_" : "_{$sourceLine}_";

        return "• **[{$article['title']}]({$article['link']})**\n{$content}\n\n";
    }

    private function paginateFields(Collection $fields): array
    {
        $embeds = [];
        $page = 1;
        $current = $this->baseEmbed($page);
        $currentLength = mb_strlen($current['title']) + mb_strlen($current['description']);

        foreach ($fields as $field) {
            $fieldLength = mb_strlen($field['name']) + mb_strlen($field['value']);

            if (($current['fields'] ?? []) !== [] && $currentLength + $fieldLength > self::EMBED_CHAR_LIMIT) {
                $embeds[] = $current;

                if (count($embeds) >= config('ai-news.max_embed_pages')) {
                    return $embeds;
                }

                $page++;
                $current = $this->baseEmbed($page);
                $currentLength = mb_strlen($current['title']) + mb_strlen($current['description']);
            }

            $current['fields'][] = $field;
            $currentLength += $fieldLength;
        }

        if (($current['fields'] ?? []) !== [] && count($embeds) < config('ai-news.max_embed_pages')) {
            $embeds[] = $current;
        }

        return $embeds;
    }

    private function baseEmbed(int $page): array
    {
        $date = now()->toDateString();

        return [
            'title' => $page === 1 ? "📰 Daily AI News Summary - {$date}" : "📰 Daily AI News Summary - {$date} (Page {$page})",
            'description' => $page === 1
                ? 'Here are the latest updates from the world of AI.'
                : 'Additional stories continue below.',
            'color' => 0x2f3136,
            'fields' => [],
        ];
    }
}
