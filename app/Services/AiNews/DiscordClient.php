<?php

namespace App\Services\AiNews;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DiscordClient
{
    public function sendEmbeds(array $embeds): void
    {
        $channelId = config('ai-news.discord.channel_id');

        if (! config('ai-news.discord.bot_token') || ! $channelId) {
            throw new \RuntimeException('DISCORD_TOKEN and DISCORD_CHANNEL_ID must be configured.');
        }

        foreach ($embeds as $embed) {
            $this->request()
                ->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                    'embeds' => [$embed],
                ])
                ->throw();
        }
    }

    private function request(): PendingRequest
    {
        return Http::withToken(config('ai-news.discord.bot_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(3, 1000);
    }
}
