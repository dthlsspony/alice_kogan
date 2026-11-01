<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    private const COMMENTABLE = [
        'quote' => Quote::class,
        'book' => Book::class,
        'article' => Article::class,
        'page' => Page::class,
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(self::COMMENTABLE))],
            'id' => ['required', 'integer'],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
        ], [
            'body.required' => 'Введите текст комментария.',
            'body.min' => 'Комментарий слишком короткий.',
            'body.max' => 'Комментарий не длиннее 5000 символов.',
        ]);

        $commentableClass = self::COMMENTABLE[$validated['type']];

        $commentable = $commentableClass::query()->findOrFail($validated['id']);

        // Через связь: Eloquent сам подставит морф-алиас ('quote'|'book'|'article') в commentable_type.
        $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'status' => Comment::STATUS_PENDING,
        ]);

        return back()->with('success', 'Комментарий отправлен и появится на сайте после проверки модератором.');
    }
}
