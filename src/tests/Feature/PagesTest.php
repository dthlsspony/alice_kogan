<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a static page by its slug', function () {
    Page::create([
        'title' => 'Контакты',
        'slug' => 'contacts',
        'body' => '<p>Напишите нам на почту.</p>',
        'is_published' => true,
    ]);

    $this->get('/contacts')
        ->assertOk()
        ->assertSee('Контакты')
        ->assertSee('Напишите нам на почту.');
});

it('renders nested slugs like about/author', function () {
    Page::create([
        'title' => 'Об авторе',
        'slug' => 'about/author',
        'body' => '<p>Биография автора проекта.</p>',
        'is_published' => true,
    ]);

    $this->get('/about/author')
        ->assertOk()
        ->assertSee('Об авторе');
});

it('hides unpublished pages', function () {
    Page::create([
        'title' => 'Скрытая страница',
        'slug' => 'hidden',
        'body' => '<p>Черновик.</p>',
        'is_published' => false,
    ]);

    $this->get('/hidden')->assertNotFound();
});

it('returns 404 for unknown slugs', function () {
    $this->get('/no-such-page')->assertNotFound();
});

it('shows pages management to admins and displays a created page', function () {
    $admin = User::factory()->admin()->create();

    // Страница создана через админку (Filament-ресурс Pages).
    Page::create([
        'title' => 'Новая страница',
        'slug' => 'novaya',
        'body' => '<p>Содержимое новой страницы.</p>',
        'is_published' => true,
    ]);

    $this->actingAs($admin)->get('/admin/pages')->assertOk();

    $this->get('/novaya')
        ->assertOk()
        ->assertSee('Содержимое новой страницы.');
});
