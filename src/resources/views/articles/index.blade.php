@extends('layouts.app')

@section('title', ' — Статьи')

@section('content')
    <p class="redheader" style="font-size: 16pt;">Статьи</p>

    @if ($articles->isEmpty())
        <p class="simple">Статей пока нет.</p>
    @else
        <div class="k-article-list">
            @foreach ($articles as $article)
                <div class="k-article-row">
                    @if ($article->published_at)
                        <span class="k-article-date">{{ $article->published_at->translatedFormat('d.m.Y') }}</span>
                    @endif
                    <a class="k-article-title" href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                    @if ($article->excerpt)
                        <div class="k-article-excerpt">{{ $article->excerpt }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="k-pagination">{{ $articles->links() }}</div>
@endsection
