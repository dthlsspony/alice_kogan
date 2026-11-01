<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\Widget;

class PopularPagesWidget extends Widget
{
    protected string $view = 'filament.widgets.popular-pages';

    protected ?string $heading = 'Популярные страницы (30 дней)';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $rows = Visit::query()
            ->selectRaw('path, COUNT(*) as total, COUNT(DISTINCT session_id) as sessions')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'heading' => $this->heading,
            'rows' => $rows,
        ];
    }
}
