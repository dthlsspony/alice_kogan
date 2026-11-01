<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ $heading }}</x-slot>
        <x-slot name="description">
            Модерация — раздел «Публикации → Комментарии»
        </x-slot>

        @if ($comments->isEmpty())
            <p class="p-4 text-sm text-gray-500">Комментариев пока нет.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($comments as $comment)
                    <li class="px-4 py-3 text-sm">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium">{{ $comment->user?->name ?? 'Гость' }}</span>
                            <span class="text-xs text-gray-400">{{ $comment->created_at->translatedFormat('d.m.Y H:i') }}</span>
                            @if ($comment->status === 'pending')
                                <span class="text-xs rounded-full bg-warning-100 text-warning-700 px-2 py-0.5">на проверке</span>
                            @elseif ($comment->status === 'approved')
                                <span class="text-xs rounded-full bg-success-100 text-success-700 px-2 py-0.5">опубликован</span>
                            @endif
                        </div>
                        <p class="text-gray-700 line-clamp-2">{{ Str::limit($comment->body, 160) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
