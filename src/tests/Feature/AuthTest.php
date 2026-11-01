<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows registration page to guests', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Регистрация');
});

it('allows a visitor to register as a regular user', function () {
    $response = $this->post(route('register.attempt'), [
        'name' => 'Иван Тестов',
        'email' => 'ivan@example.com',
        'password' => 'super-secret-1',
        'password_confirmation' => 'super-secret-1',
    ]);

    $response->assertRedirect(route('home'));

    $user = User::query()->where('email', 'ivan@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->is_admin)->toBeFalse()
        ->and(auth()->check())->toBeTrue();
});

it('rejects registration with an existing email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.attempt'), [
        'name' => 'Кто-то',
        'email' => 'taken@example.com',
        'password' => 'super-secret-1',
        'password_confirmation' => 'super-secret-1',
    ])->assertSessionHasErrors('email');
});

it('logs a user in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'super-secret-1']);

    $this->post(route('login.attempt'), [
        'email' => $user->email,
        'password' => 'super-secret-1',
    ])->assertRedirect(route('home'));

    expect(auth()->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('hides the admin panel from regular users (404)', function () {
    $this->actingAs(User::factory()->create())
        ->get(url('/admin'))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(url('/admin/users'))
        ->assertNotFound();
});

it('hides the admin panel from guests (404)', function () {
    $this->get(url('/admin'))->assertNotFound();
    $this->get(url('/admin/users'))->assertNotFound();
});

it('hides the admin login page completely', function () {
    $this->get(url('/admin/login'))->assertNotFound();
    $this->get(url('/admin/password-reset/request'))->assertNotFound();

    // Даже администратору страница входа в админку не нужна — вход общий.
    $this->actingAs(User::factory()->admin()->create())
        ->get(url('/admin/login'))
        ->assertNotFound();
});

it('lets admins enter the panel through the common site login', function () {
    $admin = User::factory()->admin()->create(['password' => 'super-secret-1']);

    $this->post(route('login.attempt'), [
        'email' => $admin->email,
        'password' => 'super-secret-1',
    ])->assertRedirect(url('/admin'));

    // Та же сессия открывает админку.
    $this->get(url('/admin'))->assertOk();
});

it('sends regular users to the home page after login', function () {
    $user = User::factory()->create(['password' => 'super-secret-1']);

    $this->post(route('login.attempt'), [
        'email' => $user->email,
        'password' => 'super-secret-1',
    ])->assertRedirect(route('home'));
});

it('allows admins into the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(url('/admin'))
        ->assertOk();
});

it('shows the users list to admins', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(url('/admin/users'))
        ->assertOk();
});

it('shows the admin link in the header only to admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('Админка');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Админка');
});

it('does not show the admin link to guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Админка')
        ->assertSee('Регистрация');
});
