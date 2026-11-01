<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Book;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Quote;
use App\Services\Seo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'quote' => Quote::class,
            'book' => Book::class,
            'article' => Article::class,
            'page' => Page::class,
        ]);

        // Администратору доступны все действия.
        Gate::before(fn ($user, $ability) => $user?->is_admin ? true : null);

        // Общее меню сайта для всех страниц (данные из админки).
        View::composer('layouts.app', function ($view) {
            $view->with('menuItems', MenuItem::visibleTree());
        });
    }
}
