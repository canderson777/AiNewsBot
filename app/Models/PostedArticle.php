<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostedArticle extends Model
{
    protected $fillable = [
        'link',
        'link_hash',
        'title',
        'source',
        'category',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
