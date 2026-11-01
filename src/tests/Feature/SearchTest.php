<?php

use App\Models\Article;
use App\Models\Book;
use App\Models\Page;
use App\Models\Quote;
use App\Models\Tag;
use App\Models\TagCategory;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds quotes by full-text query on the page', function () {
    $natureBook = Book::factory()->create(['title' => 'Книга о природе', 'author' => 'Иван Природин']);
    $cityBook = Book::factory()->create(['title' => 'Книга о городе', 'author' => 'Пётр Градов']);
    Quote::factory()->for($natureBook)->create(['text' => 'Любовь к природе лечит душу и тело']);
    Quote::factory()->for($cityBook)->create(['text' => 'Совсем другой текст про мегаполис']);

    $this->get(route('search.results', ['query' => 'природ']))
        ->assertOk()
        ->assertSee('Любовь к природе лечит душу и тело')
        ->assertDontSee('Совсем другой текст про мегаполис');
});

it('finds quotes by book title', function () {
    $book = Book::factory()->create(['title' => 'Уникальное название издания']);
    Quote::factory()->for($book)->create(['text' => 'Какой-то текст цитаты']);

    $this->get(route('search.results', ['query' => 'Уникальное название']))
        ->assertOk()
        ->assertSee('Какой-то текст цитаты');
});

it('filters quotes by tags', function () {
    $category = TagCategory::create(['name' => 'Тест', 'slug' => 'test', 'color' => '#000000']);
    $tagA = Tag::create(['name' => 'Тег А', 'slug' => 'teg-a', 'category_id' => $category->id]);
    $tagB = Tag::create(['name' => 'Тег Б', 'slug' => 'teg-b', 'category_id' => $category->id]);

    $quoteA = Quote::factory()->create(['text' => 'Цитата с тегом А']);
    $quoteA->tags()->attach($tagA);
    $quoteB = Quote::factory()->create(['text' => 'Цитата с тегом Б']);
    $quoteB->tags()->attach($tagB);

    $this->get(route('search.results', ['tags' => [$tagA->id]]))
        ->assertOk()
        ->assertSee('Цитата с тегом А')
        ->assertDontSee('Цитата с тегом Б');
});

it('finds articles, pages and books with type filtering', function () {
    $admin = User::factory()->admin()->create();

    Article::factory()->create([
        'user_id' => $admin->id,
        'title' => 'Статья о воде',
        'slug' => 'statya-o-vode',
        'body' => '<p>Вода — основа жизни на земле.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    Page::create([
        'title' => 'Водная страница',
        'slug' => 'vodnaya',
        'body' => '<p>Всё о воде и её свойствах.</p>',
        'is_published' => true,
    ]);

    Book::factory()->create(['title' => 'Море и вода', 'author' => 'А. Морской']);

    Quote::factory()->create(['text' => 'Тише воды, ниже травы']);

    // Поиск «вод» находит все четыре типа.
    $result = app(SearchService::class)->search('вод');
    expect($result->paginator->total())->toBeGreaterThanOrEqual(4)
        ->and($result->counts['article'])->toBe(1)
        ->and($result->counts['page'])->toBe(1)
        ->and($result->counts['book'])->toBe(1)
        ->and($result->counts['quote'])->toBe(1);

    // Фильтр по типам: только статьи.
    $onlyArticles = app(SearchService::class)->search('вод', [], ['article']);
    expect($onlyArticles->paginator->count())->toBe(1)
        ->and($onlyArticles->paginator->first()['type'])->toBe('article')
        ->and($onlyArticles->paginator->first()['title'])->toBe('Статья о воде');

    // Фильтр по нескольким типам: цитаты + страницы.
    $quotesAndPages = app(SearchService::class)->search('вод', [], ['quote', 'page']);
    expect($quotesAndPages->paginator->count())->toBe(2)
        ->and(collect($quotesAndPages->paginator->pluck('type')->all())->unique()->sort()->values()->all())
        ->toBe(['page', 'quote']);
});

it('searches pages on the web page', function () {
    Page::create([
        'title' => 'Редкое слово',
        'slug' => 'redkoe',
        'body' => '<p>Контент с уникальным словом зюйдвестка.</p>',
        'is_published' => true,
    ]);

    $this->get(route('search.results', ['query' => 'зюйдвестка']))
        ->assertOk()
        ->assertSee('Редкое слово')
        ->assertSee('Страницы');
});

it('returns json from the api endpoint', function () {
    $book = Book::factory()->create(['title' => 'Апи Книга']);
    Quote::factory()->for($book)->create(['text' => 'Текст для апи-поиска']);

    $this->getJson(route('api.quotes', ['query' => 'апи']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'items' => [['type', 'id', 'snippet', 'url']],
                'counts',
                'pagination' => ['total', 'page', 'per_page', 'total_pages'],
            ],
        ]);

    $data = $this->getJson('/api/quotes?query='.rawurlencode('апи'))->json('data');
    expect($data['pagination']['total'])->toBe(1)
        ->and($data['items'][0]['type'])->toBe('quote');
});
