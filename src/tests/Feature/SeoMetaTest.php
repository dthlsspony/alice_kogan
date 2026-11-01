<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use function Pest\Laravel\get;

it('renders seo title and description for an article', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $article = Article::query()->create([
        'user_id' => $admin->id,
        'title' => 'Обычный заголовок',
        'slug' => 'seo-test',
        'body' => '<p>Текст</p>',
        'seo_title' => 'SEO-заголовок статьи',
        'meta_description' => 'SEO-описание статьи для поисковиков',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    get("/articles/{$article->slug}")
        ->assertOk()
        ->assertSee('<title>SEO-заголовок статьи</title>', false)
        ->assertSee('content="SEO-описание статьи для поисковиков"', false);
});

it('falls back to article title and excerpt without seo fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $article = Article::query()->create([
        'user_id' => $admin->id,
        'title' => 'Просто статья',
        'slug' => 'seo-fallback',
        'excerpt' => 'Краткое описание статьи',
        'body' => '<p>Текст</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    get("/articles/{$article->slug}")
        ->assertOk()
        ->assertSee('<title>Просто статья — Сайт Когана</title>', false)
        ->assertSee('content="Краткое описание статьи"', false);
});
