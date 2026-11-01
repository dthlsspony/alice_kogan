<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ $heading }}</x-slot>

        @if ($rows->isEmpty())
            <p class="p-4 text-sm text-gray-500">Пока нет данных о посещениях.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2 px-4 font-medium">Страница</th>
                        <th class="py-2 px-4 font-medium text-right">Просмотры</th>
                        <th class="py-2 px-4 font-medium text-right">Сессии</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-4 font-mono text-xs">
                                @if ($row->path === '/')
                                    <a href="{{ url('/') }}" class="text-primary-600 hover:underline" target="_blank">/</a>
                                @else
                                    <a href="{{ url($row->path) }}" class="text-primary-600 hover:underline" target="_blank">{{ $row->path }}</a>
                                @endif
                            </td>
                            <td class="py-2 px-4 text-right">{{ number_format($row->total, 0, ',', ' ') }}</td>
                            <td class="py-2 px-4 text-right text-gray-500">{{ number_format($row->sessions, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
