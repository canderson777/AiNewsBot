<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posted_articles', function (Blueprint $table): void {
            $table->id();
            $table->text('link');
            $table->char('link_hash', 64)->unique();
            $table->string('title')->nullable();
            $table->string('source')->nullable();
            $table->string('category')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posted_articles');
    }
};
