@extends('layouts.app')

@section('title', ' — ' . e($article->title))

@section('content')
    <div class="k-backlink"><a href="{{ route('articles.index') }}">&lArr; Назад к статьям</a></div>

    @if ($article->published_at)
        <p class="k-pubdate">{{ $article->published_at->translatedFormat('d F Y') }}</p>
    @endif

    @if (! str_contains($article->body, 'class="header"'))
        <p class="redheader">{{ $article->title }}</p>
    @endif

    <div class="k-page">
        {!! $article->body !!}
    </div>

    @include('partials.comments', [
        'commentable' => $article,
        'commentableType' => $commentableType,
        'comments' => $comments,
    ])
@endsection
