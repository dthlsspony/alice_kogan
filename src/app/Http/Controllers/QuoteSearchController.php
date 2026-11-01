<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteSearchController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    /**
     * Universal search API (quotes, books, articles, pages)
     */
    public function search(Request $request): JsonResponse
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
            (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $result->paginator->values()->all(),
                'counts' => $result->counts,
                'pagination' => [
                    'total' => $result->paginator->total(),
                    'page' => $result->paginator->currentPage(),
                    'per_page' => $result->paginator->perPage(),
                    'total_pages' => $result->paginator->lastPage(),
                ],
            ],
        ]);
    }
}
