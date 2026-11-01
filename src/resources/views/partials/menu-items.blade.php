@php
    /**
     * Рекурсивный рендер пунктов меню.
     *
     * @var \Illuminate\Support\Collection<int, \App\Models\MenuItem> $items
     * @var bool $mobile
     */
    $currentPath = request()->path() === '/' ? '/' : request()->path();
@endphp
@foreach($items as $item)
    @php
        $url = $item->url ?: '#';
        $isExternal = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
        $path = $isExternal ? null : '/' . ltrim($url, '/');
        $isActive = $path !== null && (
            rtrim($path, '/') === '/' . ($currentPath === '/' ? '' : ltrim($currentPath, '/'))
            || ($currentPath !== '/' && str_starts_with('/' . ltrim($currentPath, '/'), rtrim($path, '/') . '/'))
        );
    @endphp
    <div class="{{ $mobile ? '' : 'relative group' }}">
        <a href="{{ $url }}"
           @if($isExternal && $item->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
           class="{{ $mobile
               ? 'block px-4 py-2 rounded-lg text-sm font-medium transition ' . ($isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700')
               : 'px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-1 ' . ($isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700') }}"
           {{ $mobile ? '' : 'aria-haspopup="true"' }}>
            {{ $item->label }}
            @if($item->children->isNotEmpty() && ! $mobile)
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            @endif
        </a>

        @if($item->children->isNotEmpty())
            @if($mobile)
                <div class="ml-4 mt-1 mb-1 flex flex-col space-y-1 border-l border-gray-200 dark:border-gray-700 pl-2">
                    @include('partials.menu-items', ['items' => $item->children, 'mobile' => true])
                </div>
            @else
                <div class="absolute left-0 top-full pt-1 hidden group-hover:block z-50">
                    <div class="min-w-48 py-2 rounded-xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700">
                        @include('partials.menu-items', ['items' => $item->children, 'mobile' => true])
                    </div>
                </div>
            @endif
        @endif
    </div>
@endforeach
