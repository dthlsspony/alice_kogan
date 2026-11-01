<?php

use App\Filament\Resources\ArticleResource\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('replaces the banner when editing an article', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $article = Article::factory()->create([
        'image_path' => 'articles/banners/old.svg',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    Storage::disk('public')->put('articles/banners/old.svg', '<svg/>');

    // ->upload() имитирует реальную браузерную загрузку Livewire.
    $livewire = Livewire\Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->upload('data.image_path', [UploadedFile::fake()->createWithContent('new-banner.jpg', "\xFF\xD8\xFF\xE0NEWDATA")])
        ->call('save');

    $livewire->assertHasNoErrors();

    $article->refresh();
    expect($article->image_path)->not->toBe('articles/banners/old.svg')
        ->and($article->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($article->image_path))->toBeTrue();
});
