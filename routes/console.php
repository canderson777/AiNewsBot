<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:post')
    ->twiceDaily(9, 21)
    ->timezone(config('ai-news.schedule_timezone'))
    ->withoutOverlapping();
