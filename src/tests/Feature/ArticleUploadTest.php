<?php

use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates an article with an uploaded banner as admin', function () {
    // Без fake файл попал бы в реальный диск и остался там после отката БД.
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $livewire = Livewire\Livewire::test(CreateArticle::class)
        ->set('data.title', 'Статья с картинкой')
        ->set('data.slug', 'statya-s-kartinkoy')
        ->set('data.body', '<p>Тело статьи с картинкой.</p>')
        ->set('data.is_published', true)
        ->set('data.published_at', now()->format('Y-m-d H:i:s'))
        ->set('data.image_path', UploadedFile::fake()->createWithContent('banner.jpg', "\xFF\xD8\xFF\xE0FAKEJPEGDATA"));

    $livewire->call('create');

    $livewire->assertHasNoErrors();

    $article = Article::query()->where('slug', 'statya-s-kartinkoy')->first();
    expect($article)->not->toBeNull()
        ->and($article->image_path)->not->toBeNull();

    expect(Storage::disk('public')->exists($article->image_path))->toBeTrue();
});
