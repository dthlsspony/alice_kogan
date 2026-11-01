@php
    /**
     * Блок комментариев: список одобренных + форма (для авторизованных).
     *
     * @var string $commentableType  'quote'|'book'|'article'
     * @var mixed  $commentable
     * @var \Illuminate\Support\Collection<int, \App\Models\Comment> $comments
     */
    $comments = $comments ?? collect();
@endphp
<section class="mt-10" id="comments">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
        Комментарии
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $comments->count() }})</span>
    </h2>

    @if($comments->isNotEmpty())
        <div class="space-y-4 mb-8">
            @foreach($comments as $comment)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-sm font-semibold shrink-0">
                            {{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->user?->name ?? 'Гость' }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ $comment->created_at->translatedFormat('d F Y в H:i') }}</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $comment->body }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Пока нет комментариев. Будьте первым!</p>
    @endif

    @auth
        <form action="{{ route('comments.store') }}" method="POST"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 sm:p-5">
            @csrf
            <input type="hidden" name="type" value="{{ $commentableType }}">
            <input type="hidden" name="id" value="{{ $commentable->id }}">
            <label for="comment-body-{{ $commentableType }}-{{ $commentable->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Ваш комментарий
            </label>
            <textarea
                id="comment-body-{{ $commentableType }}-{{ $commentable->id }}"
                name="body"
                rows="3"
                required
                maxlength="5000"
                placeholder="Напишите комментарий..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-y"
            >{{ old('body') }}</textarea>
            @error('body')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <div class="mt-3 flex items-center justify-between flex-wrap gap-3">
                <p class="text-xs text-gray-400 dark:text-gray-500">Комментарий появится на сайте после проверки модератором.</p>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    Отправить
                </button>
            </div>
        </form>
    @else
        <div class="bg-gray-100 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-sm text-gray-600 dark:text-gray-400">
            Чтобы оставить комментарий,
            <a href="{{ route('login') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">войдите</a>
            или
            <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">зарегистрируйтесь</a>.
        </div>
    @endauth
</section>
