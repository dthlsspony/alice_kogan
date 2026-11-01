<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Book;
use App\Models\Page;
use App\Models\Quote;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Универсальный поиск по всем типам контента средствами MariaDB.
 *
 * - Цитаты: FULLTEXT по quotes.text (BOOLEAN MODE, префиксные токены) + фильтр по тегам.
 * - Статьи и страницы: FULLTEXT по title+body.
 * - Книги: LIKE по title/author/description.
 * Результаты всех типов объединяются и сортируются по релевантности.
 * На SQLite (тесты) FULLTEXT заменяется LIKE-стратегией.
 */
class SearchService
{
    public const TYPES = ['quote', 'book', 'article', 'page'];

    private const TYPE_PRIORITY = ['quote' => 0, 'article' => 1, 'page' => 2, 'book' => 3];

    /**
     * @param  array<int, string>  $types  Типы для поиска; пустой массив — все.
     */
    public function search(
        ?string $query,
        array $tagIds = [],
        array $types = [],
        int $page = 1,
        int $perPage = 20,
    ): SearchResult {
        $types = array_values(array_intersect(self::TYPES, $types));

        // Ограничиваем размер страницы: per_page — внешний параметр API.
        $perPage = max(1, min(100, $perPage));

        if ($types === []) {
            $types = self::TYPES;
        }

        $query = trim((string) $query);
        $hasQuery = $query !== '';

        $items = collect();

        if (! $hasQuery) {
            // Без запроса — только цитаты, отфильтрованные по тегам.
            if (in_array('quote', $types, true)) {
                $items = $this->fetchQuotes(null, $tagIds, $hasQuery);
            }
        } else {
            if (in_array('quote', $types, true)) {
                $items = $items->merge($this->fetchQuotes($query, $tagIds, $hasQuery));
            }
            if (in_array('book', $types, true)) {
                $items = $items->merge($this->fetchBooks($query));
            }
            if (in_array('article', $types, true)) {
                $items = $items->merge($this->fetchFulltextModels(
                    Article::query()->published(),
                    ['title', 'body'],
                    $query,
                    'article',
                ));
            }
            if (in_array('page', $types, true)) {
                $items = $items->merge($this->fetchFulltextModels(
                    Page::query()->published(),
                    ['title', 'body'],
                    $query,
                    'page',
                ));
            }
        }

        $items = $items->sortByDesc('relevance')->values();

        $counts = $types === self::TYPES || count($types) > 1
            ? $items->groupBy('type')->map->count()->all()
            : [$types[0] => $items->count()];

        $total = $items->count();
        $page = max(1, $page);
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => url('/search')],
        );

        return new SearchResult($paginator, $counts);
    }

    /**
     * Цитаты: FULLTEXT + фильтр по тегам + LIKE по книге.
     */
    private function fetchQuotes(?string $query, array $tagIds, bool $hasQuery): Collection
    {
        $q = Quote::query()
            ->select('quotes.*')
            ->with(['book', 'tags'])
            ->withCount(['comments' => fn ($c) => $c->where('status', 'approved')])
            ->when($tagIds !== [], fn ($w) => $w->whereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds)));

        if ($query !== null) {
            $boolean = $this->booleanQuery($query);

            if ($this->supportsFulltext()) {
                // Литерал вместо ?-биндинга: raw-биндинги где/order конфликтуют
                // со scope-биндингами (HY093).
                $match = 'MATCH(quotes.text) AGAINST('.DB::getPdo()->quote($boolean).' IN BOOLEAN MODE)';
                $q->addSelect(DB::raw($match.' as relevance'))
                    ->where(function ($w) use ($query, $match) {
                        $w->whereRaw($match.' > 0')
                            ->orWhereHas('book', fn ($b) => $b->where(function ($x) use ($query) {
                                $x->where('title', 'like', "%{$query}%")
                                    ->orWhere('author', 'like', "%{$query}%");
                            }));
                    })
                    ->orderByRaw($match.' DESC');
            } else {
                $q->addSelect(DB::raw('1 as relevance'))
                    ->where(function ($w) use ($query) {
                        $w->where('quotes.text', 'like', "%{$query}%")
                            ->orWhereHas('book', fn ($b) => $b->where(function ($x) use ($query) {
                                $x->where('title', 'like', "%{$query}%")
                                    ->orWhere('author', 'like', "%{$query}%");
                            }));
                    });
            }
        } else {
            $q->addSelect(DB::raw('0 as relevance'))->orderByDesc('quotes.created_at');
        }

        return $q->limit(500)->get()->map(fn (Quote $quote) => [
            'type' => 'quote',
            'id' => $quote->id,
            'title' => null,
            'snippet' => $quote->text,
            'url' => route('quotes.show', $quote),
            'tags' => $quote->tags,
            'meta' => array_filter([
                'book' => $quote->book?->title,
                'author' => $quote->book?->author,
                'page' => $quote->page_number ? "стр. {$quote->page_number}" : null,
                'comments' => $quote->comments_count,
            ]),
            'relevance' => (float) ($quote->relevance ?? 0),
        ]);
    }

    /**
     * Книги: LIKE по названию, автору, описанию.
     */
    private function fetchBooks(string $query): Collection
    {
        return Book::query()
            ->withCount('quotes')
            ->where(function ($w) use ($query) {
                $w->where('title', 'like', "%{$query}%")
                    ->orWhere('author', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('title')
            ->limit(200)
            ->get()
            ->map(fn (Book $book) => [
                'type' => 'book',
                'id' => $book->id,
                'title' => $book->title,
                'snippet' => Str::limit(strip_tags((string) $book->description), 220),
                'url' => route('books.show', $book),
                'image' => $book->image_path,
                'meta' => array_filter([
                    'author' => $book->author,
                    'quotes' => $book->quotes_count,
                ]),
                'relevance' => 1.0,
            ]);
    }

    /**
     * Статьи и страницы: FULLTEXT (или LIKE на SQLite) по title+body.
     */
    private function fetchFulltextModels($query, array $columns, string $search, string $type): Collection
    {
        $boolean = $this->booleanQuery($search);

        if ($this->supportsFulltext()) {
            // Литерал вместо ?-биндинга (см. комментарий в fetchQuotes).
            // selectRaw вместо addSelect: addSelect на пустом селекте заменил бы * .
            $match = 'MATCH('.implode(', ', $columns).') AGAINST('
                .DB::getPdo()->quote($boolean).' IN BOOLEAN MODE)';
            $query->selectRaw('* , '.$match.' as relevance')
                ->whereRaw($match.' > 0')
                ->orderByRaw($match.' DESC');
        } else {
            $query->selectRaw('* , 1 as relevance')->where(function ($w) use ($columns, $search) {
                foreach ($columns as $column) {
                    $w->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $query->limit(300)->get()->map(fn ($model) => [
            'type' => $type,
            'id' => $model->id,
            'title' => $model->title,
            'snippet' => Str::limit(
                strip_tags((string) ($model->excerpt ?: $model->body)),
                220,
            ),
            'url' => $type === 'article'
                ? route('articles.show', $model)
                : url('/'.ltrim((string) $model->slug, '/')),
            'meta' => array_filter([
                'date' => $type === 'article'
                    ? $model->published_at?->translatedFormat('d.m.Y')
                    : $model->created_at?->translatedFormat('d.m.Y'),
            ]),
            'relevance' => (float) ($model->relevance ?? 0),
        ]);
    }

    private function supportsFulltext(): bool
    {
        return in_array(DB::getDriverName(), ['mariadb', 'mysql'], true);
    }

    /**
     * Запрос → выражение BOOLEAN MODE ("любовь зима" => "+любовь* +зима*").
     */
    private function booleanQuery(string $query): string
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($query), $matches);

        return collect($matches[0] ?? [])
            ->filter(fn ($word) => mb_strlen($word) >= 2)
            ->map(fn ($word) => '+'.$word.'*')
            ->implode(' ');
    }
}
