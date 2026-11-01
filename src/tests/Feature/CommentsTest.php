<?php

use App\Models\Article;
use App\Models\Comment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to post a comment', function () {
    $article = Article::factory()->create();

    $this->post(route('comments.store'), [
        'type' => 'article',
        'id' => $article->id,
        'body' => 'Тестовый комментарий',
    ])->assertRedirect(route('login'));

    expect(Comment::count())->toBe(0);
});

it('stores a guest-submitted comment as pending and hides it from the page', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create();

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'type' => 'article',
            'id' => $article->id,
            'body' => 'Отличная статья!',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $comment = Comment::query()->sole();
    expect($comment->status)->toBe(Comment::STATUS_PENDING)
        ->and($comment->user_id)->toBe($user->id)
        ->and($comment->commentable_type)->toBe('article');

    // На странице комментарий до одобрения не виден.
    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertDontSee('Отличная статья!');
});

it('shows an approved comment on the page', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create();

    $comment = $article->comments()->create([
        'user_id' => $user->id,
        'body' => 'Одобренный комментарий',
        'status' => Comment::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('Одобренный комментарий')
        ->assertSee($user->name);

    expect($comment->fresh()->commentable_type)->toBe('article');
});

it('rejects too short comment bodies', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create();

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'type' => 'article',
            'id' => $article->id,
            'body' => 'ок',
        ])
        ->assertSessionHasErrors('body');

    expect(Comment::count())->toBe(0);
});

it('rejects unknown commentable types', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create();

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'type' => 'user',
            'id' => $user->id,
            'body' => 'Попробуем постить на пользователя',
        ])
        ->assertSessionHasErrors('type');

    expect(Comment::count())->toBe(0);
});

it('accepts comments on static pages', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $this->actingAs($user)
        ->post(route('comments.store'), [
            'type' => 'page',
            'id' => $page->id,
            'body' => 'Комментарий к странице',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $comment = $page->comments()->sole();
    expect($comment->status)->toBe(Comment::STATUS_PENDING)
        ->and($comment->commentable_type)->toBe('page')
        ->and($comment->user_id)->toBe($user->id);
});

it('shows an approved page comment on the page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create(['slug' => 'comments-test-page']);

    $page->comments()->create([
        'user_id' => $user->id,
        'body' => 'Одобренный комментарий страницы',
        'status' => Comment::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    $this->get('/comments-test-page')
        ->assertOk()
        ->assertSee('Одобренный комментарий страницы');
});
