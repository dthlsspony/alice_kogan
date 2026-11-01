<?php

use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use function Pest\Laravel\get;

it('records a visit for a regular page request', function () {
    get('/')->assertOk();

    expect(Visit::query()->where('path', '/')->count())->toBe(1);
});

it('deduplicates repeat views of the same page within a session', function () {
    get('/');
    get('/');

    expect(Visit::query()->where('path', '/')->count())->toBe(1);
});

it('does not track admin, api and asset paths', function () {
    get('/admin');
    get('/api/quotes?query=test');
    get('/images/kogan/razdelitel.gif');

    expect(Visit::query()->count())->toBe(0);
});

it('does not track bot user agents', function () {
    get('/', ['User-Agent' => 'Googlebot/2.1']);

    expect(Visit::query()->count())->toBe(0);
});
