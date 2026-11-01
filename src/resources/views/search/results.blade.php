@extends('layouts.app')

@php
    $typeLabels = [
        'quote' => 'Цитаты',
        'book' => 'Книги',
        'article' => 'Статьи',
        'page' => 'Страницы',
    ];
    $typeBadgeClasses = [
        'quote' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300',
        'book' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300',
        'article' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300',
        'page' => 'bg-sky-100 dark:bg-sky-900/40 text-sky-800 dark:text-sky-300',
    ];
    $hasFilters = $query !== '' || !empty($selectedTags) || !empty($selectedTypes);
@endphp

@section('title', ' — Результаты поиска')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Search Header -->
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Результаты поиска
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Найдено результатов: <strong>{{ $items->total() }}</strong>
            @if($query)
                по запросу: <strong>{{ $query }}</strong>
            @endif
        </p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:w-64 shrink-0">
            <form action="{{ route('search.results') }}" method="GET" class="space-y-6" id="filter-form">
                <input type="hidden" name="query" value="{{ $query }}">

                <!-- Content type filter -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Искать в</h3>
                    <div class="space-y-2">
                        @foreach($typeLabels as $type => $label)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    name="types[]"
                                    value="{{ $type }}"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 tag-checkbox"
                                    {{ empty($selectedTypes) || in_array($type, $selectedTypes) ? 'checked' : '' }}
                                >
                                <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition">
                                    {{ $label }}
                                    @if(isset($counts[$type]))
                                        <span class="text-xs text-gray-400">({{ $counts[$type] }})</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Tags Filter (applies to quotes) -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Теги цитат
                    </h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        @foreach($tags as $tag)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    name="tags[]"
                                    value="{{ $tag->id }}"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 tag-checkbox"
                                    {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }}
                                >
                                <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    Применить
                </button>
            </form>
        </aside>

        <!-- Results -->
        <div class="flex-1">
            @if($items->total() > 0)
                <div class="space-y-4">
                    @foreach($items as $item)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm hover:shadow-md transition">
                            <div class="flex gap-4">
                                @if($item['image'] ?? null)
                                    <img src="{{ storage_image($item['image']) }}" alt=""
                                         class="w-12 h-16 object-cover rounded-md shrink-0 hidden sm:block bg-gray-100 dark:bg-gray-700" loading="lazy">
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeBadgeClasses[$item['type']] }}">
                                            {{ $typeLabels[$item['type']] }}
                                        </span>
                                        @if($item['meta']['date'] ?? null)
                                            <span class="text-xs text-gray-400">{{ $item['meta']['date'] }}</span>
                                        @endif
                                    </div>

                                    @if($item['title'])
                                        <a href="{{ $item['url'] }}" class="text-lg font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                            {{ $item['title'] }}
                                        </a>
                                    @endif

                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed {{ $item['title'] ? 'mt-1' : 'text-base' }}">
                                        @if($item['type'] === 'quote')
                                            &laquo;{{ $item['snippet'] }}&raquo;
                                        @else
                                            {{ $item['snippet'] }}
                                        @endif
                                    </p>

                                    <div class="flex flex-wrap items-center gap-3 text-sm mt-3">
                                        @if($item['meta']['book'] ?? null)
                                            <span class="text-gray-500 dark:text-gray-400">{{ $item['meta']['book'] }}</span>
                                        @endif
                                        @if($item['meta']['author'] ?? null)
                                            <span class="text-gray-500 dark:text-gray-400">{{ $item['meta']['author'] }}</span>
                                        @endif
                                        @if($item['meta']['page'] ?? null)
                                            <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ $item['meta']['page'] }}</span>
                                        @endif
                                        @if($item['meta']['quotes'] ?? null)
                                            <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ $item['meta']['quotes'] }} {{ trans_choice('цитата|цитаты|цитат', $item['meta']['quotes']) }}</span>
                                        @endif
                                        <a href="{{ $item['url'] }}" class="ml-auto text-indigo-600 dark:text-indigo-400 hover:underline">
                                            @if($item['type'] === 'quote')
                                                Обсудить
                                                @if(($item['meta']['comments'] ?? 0) > 0)
                                                    <span class="text-gray-400">({{ $item['meta']['comments'] }})</span>
                                                @endif
                                            @else
                                                Открыть
                                            @endif
                                        </a>
                                    </div>

                                    @if($item['type'] === 'quote' && ($item['tags'] ?? collect())->isNotEmpty())
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @foreach($item['tags'] as $tag)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($items->lastPage() > 1)
                    <nav class="flex items-center justify-center gap-2 mt-8 flex-wrap" aria-label="Пагинация">
                        @for($i = 1; $i <= $items->lastPage(); $i++)
                            <a
                                href="{{ $items->withQueryString()->url($i) }}"
                                class="w-10 h-10 flex items-center justify-center rounded-lg text-sm font-medium transition {{ $i === $items->currentPage() ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                            >
                                {{ $i }}
                            </a>
                        @endfor
                    </nav>
                @endif
            @elseif($hasFilters)
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ничего не найдено</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Попробуйте изменить параметры поиска
                    </p>
                    <a href="{{ route('home') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition">
                        Вернуться к поиску
                    </a>
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Введите запрос</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Поиск работает по цитатам, книгам, статьям и страницам
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
