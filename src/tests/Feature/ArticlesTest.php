<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only published articles', function () {
    $admin = User::factory()->admin()->create();

    Article::factory()->create([
        'user_id' => $admin->id,
        'title' => 'Опубликованная статья',
        'slug' => 'publikovannaya',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    Article::factory()->create([
        'user_id' => $admin->id,
        'title' => 'Черновик',
        'slug' => 'chernovik',
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Опубликованная статья')
        ->assertDontSee('Черновик');
});

it('shows a published article by slug', function () {
    $article = Article::factory()->create([
        'title' => 'Как пользоваться поиском',
        'slug' => 'kak-polzovatsya-poiskom',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('Как пользоваться поиском');
});

it('hides unpublished articles', function () {
    $article = Article::factory()->create([
        'slug' => 'skryataya',
        'is_published' => false,
    ]);

    $this->get(route('articles.show', $article->slug))
        ->assertNotFound();
});

it('does not count future articles as published', function () {
    $article = Article::factory()->create([
        'slug' => 'budushaya',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('articles.show', $article->slug))
        ->assertNotFound();
});
