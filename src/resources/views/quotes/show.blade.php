@extends('layouts.app')

@section('title', ' — Цитата')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <a href="{{ $quote->book ? route('books.show', $quote->book) : route('books.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        @if($quote->book)
            Книга «{{ $quote->book->title }}»
        @else
            К библиографии
        @endif
    </a>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 sm:p-10 shadow-sm">
        <blockquote class="text-lg sm:text-xl text-gray-900 dark:text-gray-100 leading-relaxed mb-6">
            &laquo;{{ $quote->text }}&raquo;
        </blockquote>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            @if($quote->book)
                <span class="text-gray-500 dark:text-gray-400">{{ $quote->book->title }}</span>
                <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $quote->book->author }}</span>
            @endif
            @if($quote->page_number)
                <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                <span class="text-gray-500 dark:text-gray-400">стр. {{ $quote->page_number }}</span>
            @endif
        </div>

        @if($quote->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach($quote->tags as $tag)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @include('partials.comments', [
        'commentable' => $quote,
        'commentableType' => $commentableType,
        'comments' => $comments,
    ])
</div>
@endsection
