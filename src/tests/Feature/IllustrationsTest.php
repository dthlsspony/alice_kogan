<?php

use App\Models\Article;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates covers and banners and links them to models', function () {
    Storage::fake('public');

    Book::factory()->create(['title' => 'Тестовая книга', 'author' => 'Автор Тестов']);
    Article::factory()->create(['slug' => 'testovaya', 'title' => 'Тестовая статья']);

    $this->artisan('illustrations:generate')->assertSuccessful();

    $book = Book::query()->first();
    $article = Article::query()->first();

    expect($book->image_path)->toBe('books/covers/book-1.svg')
        ->and($article->image_path)->toBe('articles/banners/testovaya.svg')
        ->and(Storage::disk('public')->exists($book->image_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($article->image_path))->toBeTrue()
        ->and(Storage::disk('public')->exists('articles/figures/figure-1.svg'))->toBeTrue();
});

it('shows book cover on the library page', function () {
    Storage::fake('public');

    $book = Book::factory()->create(['image_path' => 'books/covers/custom.svg']);
    Storage::disk('public')->put('books/covers/custom.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

    $this->get(route('books.index'))
        ->assertOk()
        ->assertSee('storage/books/covers/custom.svg', false);
});

it('shows placeholder when a book has no cover', function () {
    Storage::fake('public');

    $book = Book::factory()->create(['image_path' => null]);

    $this->get(route('books.index'))
        ->assertOk()
        ->assertSee('images/placeholder-cover.svg', false);
});
