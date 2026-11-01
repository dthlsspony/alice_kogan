<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Tag;
use App\Services\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    /**
     * Главная страница — как на старом сайте: приветствие и две колонки
     * «События» / «Новости сайта». Поиск живёт на отдельной странице /search.
     */
    public function home(): View
    {
        return view('home');
    }

    /**
     * Universal search results page (quotes, books, articles, pages)
     */
    public function search(Request $request): View
    {
        $query = trim((string) $request->input('query', ''));
        $tagIds = collect($request->input('tags', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $types = collect($request->input('types', []))
            ->filter(fn ($type) => in_array($type, SearchService::TYPES, true))
            ->unique()
            ->values()
            ->all();

        $result = $this->searchService->search(
            $query ?: null,
            $tagIds,
            $types,
            (int) $request->input('page', 1),
            20,
        );

        $tags = Tag::with('category')->orderBy('name')->get();

        return view('search.results', [
            'result' => $result,
            'items' => $result->paginator,
            'counts' => $result->counts,
            'query' => $query,
            'selectedTags' => $tagIds,
            'selectedTypes' => $types,
            'tags' => $tags,
        ]);
    }

    /**
     * Bibliography - list of books (cards or list layout)
     */
    public function bibliography(Request $request): View
    {
        $books = Book::withCount('quotes')->orderBy('title')->get();

        return view('books.index', [
            'books' => $books,
            'viewMode' => $request->input('view') === 'list' ? 'list' : 'grid',
        ]);
    }

    /**
     * Single book page
     */
    public function book(Book $book): View
    {
        $quotes = $book->quotes()
            ->withCount(['comments' => fn ($q) => $q->where('status', 'approved')])
            ->get();

        $book->setRelation('quotes', $quotes);

        $comments = $book->comments()->approved()->with('user')->get();

        return view('books.show', [
            'book' => $book,
            'comments' => $comments,
            'commentableType' => 'book',
        ]);
    }
}
