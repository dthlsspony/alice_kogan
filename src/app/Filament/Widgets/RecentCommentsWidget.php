<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use Filament\Widgets\Widget;

class RecentCommentsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-comments';

    protected ?string $heading = 'Последние комментарии';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        return [
            'heading' => $this->heading,
            'comments' => Comment::query()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }
}
