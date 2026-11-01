@extends('layouts.app')

@section('title', ' — Библиография')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Библиография
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            Коллекция книг с цитатами
        </p>
    </div>

    <!-- View toggle -->
    <div class="flex items-center justify-center mb-8">
        <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden" role="group" aria-label="Вид отображения">
            <a href="{{ route('books.index') }}"
               class="px-4 py-2 text-sm font-medium transition inline-flex items-center gap-2 {{ ($viewMode ?? 'grid') === 'grid'
                   ? 'bg-indigo-600 text-white'
                   : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Карточки
            </a>
            <a href="{{ route('books.index', ['view' => 'list']) }}"
               class="px-4 py-2 text-sm font-medium transition inline-flex items-center gap-2 {{ ($viewMode ?? 'grid') === 'list'
                   ? 'bg-indigo-600 text-white'
                   : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Список
            </a>
        </div>
    </div>

    @if($books->count() > 0)
        @if(($viewMode ?? 'grid') === 'list')
            <!-- List layout -->
            <div class="space-y-3">
                @foreach($books as $book)
                    <a href="{{ route('books.show', $book) }}"
                       class="flex items-center gap-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-5 py-3 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition">
                        <img src="{{ storage_image($book->image_path) }}" alt="Обложка: {{ $book->title }}"
                             class="w-10 h-14 object-cover rounded-md shrink-0 bg-gray-100 dark:bg-gray-700" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                {{ $book->title }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $book->author }}
                            </div>
                        </div>
                        <div class="text-sm text-indigo-600 dark:text-indigo-400 shrink-0 whitespace-nowrap">
                            {{ $book->quotes_count }} {{ trans_choice('цитата|цитаты|цитат', $book->quotes_count) }}
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Grid layout -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($books as $book)
                    <a href="{{ route('books.show', $book) }}" class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200 flex flex-col">
                        <div class="aspect-[2/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img src="{{ storage_image($book->image_path) }}" alt="Обложка: {{ $book->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition leading-snug">
                                {{ $book->title }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                {{ $book->author }}
                            </p>
                            <div class="mt-auto flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                                <span>{{ $book->quotes_count }} {{ trans_choice('цитата|цитаты|цитат', $book->quotes_count) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Книги пока не добавлены</h3>
            <p class="text-gray-500 dark:text-gray-400">Библиография будет заполнена в ближайшее время</p>
        </div>
    @endif
</div>
@endsection
