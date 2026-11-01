<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VisitsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Посещения за 30 дней';

    protected ?string $description = 'Просмотры страниц по дням (без ботов, админки и повторов в рамках сессии)';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();

        // Агрегация на стороне БД: в память грузятся только счётчики по дням.
        $counts = Visit::query()
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('date(created_at)'))
            ->selectRaw('date(created_at) as day, count(*) as cnt')
            ->pluck('cnt', 'day');

        $labels = [];
        $data = [];

        for ($date = $from->copy(); $date <= now(); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->translatedFormat('d.m');
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Просмотры',
                    'data' => $data,
                    'borderColor' => '#A10F06',
                    'backgroundColor' => 'rgba(202, 146, 79, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
