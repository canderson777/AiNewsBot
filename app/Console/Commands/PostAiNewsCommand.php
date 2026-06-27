<?php

namespace App\Console\Commands;

use App\Services\AiNews\AiNewsPoster;
use Illuminate\Console\Command;

class PostAiNewsCommand extends Command
{
    protected $signature = 'news:post {--dry-run : Build the digest without sending it to Discord}';

    protected $description = 'Fetch fresh AI news, post a Discord digest, and remember posted articles.';

    public function handle(AiNewsPoster $poster): int
    {
        $result = $poster->post((bool) $this->option('dry-run'));

        $this->components->info(sprintf(
            'AI news digest complete: selected=%d embeds=%d posted=%s',
            $result['selected'],
            $result['embeds'],
            $result['posted'] ? 'yes' : 'no',
        ));

        return self::SUCCESS;
    }
}
