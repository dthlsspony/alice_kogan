<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Page;
use App\Models\User;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KoganStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Сайт в цифрах';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $visits7 = (int) Visit::query()->where('created_at', '>=', now()->subDays(7))->count();
        $pendingComments = Comment::query()->where('status', 'pending')->count();

        return [
            Stat::make('Визиты сегодня', Visit::query()->whereDate('created_at', today())->count())
                ->description('уникальные просмотры страниц')
                ->color('success'),

            Stat::make('Визиты за 7 дней', $visits7)
                ->description('в среднем '.number_format(round($visits7 / 7, 1), 1, ',', ' ').' в день')
                ->color('success'),

            Stat::make('Визиты за 30 дней', Visit::query()->where('created_at', '>=', now()->subDays(30))->count())
                ->color('success'),

            Stat::make('Пользователи', User::query()->count())
                ->description('зарегистрировано за 30 дней: '.User::query()->where('created_at', '>=', now()->subDays(30))->count())
                ->color('info'),

            Stat::make('Комментарии', Comment::query()->count())
                ->description('на проверке: '.$pendingComments)
                ->color($pendingComments > 0 ? 'warning' : 'info'),

            Stat::make('Статьи', Article::query()->count())
                ->description('опубликовано: '.Article::query()->published()->count())
                ->color('primary'),

            Stat::make('Страницы', Page::query()->count())
                ->description('опубликовано: '.Page::query()->published()->count())
                ->color('primary'),

            Stat::make('Книги', Book::query()->count())
                ->color('primary'),
        ];
    }
}
