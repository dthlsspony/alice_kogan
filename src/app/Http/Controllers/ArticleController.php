<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(protected Seo $seo) {}

    public function index(Request $request): View
    {
        $articles = Article::published()
            ->with('author')
            ->orderByDesc('published_at')
            ->paginate(10);

        $this->seo->set('Статьи — Сайт Когана', 'Публикации и материалы: исследования, семинары, конференции, письма и протоколы, газетные статьи.');

        return view('articles.index', [
            'articles' => $articles,
            'viewMode' => $request->input('view') === 'list' ? 'list' : 'grid',
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $comments = $article->comments()->approved()->with('user')->get();

        $this->seo->set(
            $article->seo_title ?: ($article->title.' — Сайт Когана'),
            $article->meta_description ?: $article->excerpt,
            $article->image_path,
        );

        return view('articles.show', [
            'article' => $article,
            'comments' => $comments,
            'commentableType' => 'article',
        ]);
    }
}
