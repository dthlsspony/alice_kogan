<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Seo;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(protected Seo $seo) {}

    /**
     * Рендер статической страницы по слагу (в т.ч. вложенному, например
     * about/author). Роут зарегистрирован как fallback — вызывается,
     * когда ни один явный роут не совпал.
     */
    public function show(string $slug): View
    {
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $comments = $page->comments()->approved()->with('user')->get();

        $this->seo->set(
            $page->seo_title ?: $page->title,
            $page->meta_description ?: $page->excerpt,
        );

        return view('pages.show', [
            'page' => $page,
            'comments' => $comments,
            'commentableType' => 'page',
        ]);
    }
}
