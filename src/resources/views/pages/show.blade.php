@extends('layouts.app')

@section('title', ' — ' . e($page->title))

@section('content')
    @if (! str_contains($page->body, 'class="header"'))
        <p class="redheader">{{ $page->title }}</p>
    @endif

    <div class="k-page">
        {!! $page->body !!}
    </div>

    @include('partials.comments', [
        'commentable' => $page,
        'commentableType' => $commentableType,
        'comments' => $comments,
    ])
@endsection
