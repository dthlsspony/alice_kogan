@extends('layouts.app')

@section('title', ' — ' . e($book->title))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Back Button -->
    <a href="{{ route('books.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Вернуться к библиографии
    </a>

    <!-- Book Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <div class="p-6 sm:p-8">
            <div class="flex items-start gap-4 flex-col sm:flex-row">
                <img src="{{ storage_image($book->image_path) }}" alt="Обложка: {{ $book->title }}"
                     class="w-36 sm:w-44 aspect-[2/3] object-cover rounded-xl shadow-md bg-gray-100 dark:bg-gray-700 shrink-0" loading="lazy">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $book->title }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-3">
                        {{ $book->author }}
                    </p>
                    @if($book->isbn)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ISBN: {{ $book->isbn }}
                        </p>
                    @endif
                </div>
            </div>

            @if($book->description)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $book->description }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quotes -->
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
        Цитаты из книги
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $book->quotes->count() }})</span>
    </h2>

    @if($book->quotes->count() > 0)
        <div class="space-y-4">
            @foreach($book->quotes as $quote)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                    <blockquote class="text-gray-900 dark:text-gray-100 text-base leading-relaxed mb-3">
                        &laquo;{{ $quote->text }}&raquo;
                    </blockquote>
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        @if($quote->page_number)
                            <span class="text-gray-500 dark:text-gray-400">стр. {{ $quote->page_number }}</span>
                        @endif
                        @if($quote->tags->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($quote->tags as $tag)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('quotes.show', $quote) }}"
                           class="ml-auto inline-flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            Обсудить
                            @if(($quote->comments_count ?? 0) > 0)
                                <span class="text-gray-400">({{ $quote->comments_count }})</span>
                            @endif
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Цитаты из этой книги пока не добавлены</p>
        </div>
    @endif

    @include('partials.comments', [
        'commentable' => $book,
        'commentableType' => $commentableType,
        'comments' => $comments,
    ])
</div>
@endsection
