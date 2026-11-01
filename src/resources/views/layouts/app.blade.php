@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\MenuItem> $menuItems */
    $menuItems = $menuItems ?? collect();

    // Активный раздел/подраздел и хлебные крошки — как в boxbefore.php старого сайта.
    $currentPath = '/' . ltrim(request()->path(), '/');
    $activeTop = null;
    $activeChild = null;

    foreach ($menuItems as $top) {
        $topPath = rtrim((string) $top->url, '/');

        if ($topPath !== '' && ($currentPath === $topPath || str_starts_with($currentPath, $topPath . '/'))) {
            $activeTop = $top;

            foreach ($top->children as $child) {
                $childPath = rtrim((string) $child->url, '/');

                if ($childPath !== '' && ($currentPath === $childPath || str_starts_with($currentPath, $childPath . '/'))) {
                    $activeChild = $child;
                }
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $seo = app(\App\Services\Seo::class); @endphp
    <title>{{ $seo->title() }}</title>
    <meta name="description" content="{{ $seo->description() }}">
    <link rel="canonical" href="{{ url(request()->path() === '/' ? '/' : request()->path()) }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Сайт Когана">
    <meta property="og:title" content="{{ $seo->title() }}">
    <meta property="og:description" content="{{ $seo->description() }}">
    @if ($seo->image)
        <meta property="og:image" content="{{ storage_image($seo->image) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="k-body">
<div class="k-shell">
    <header>
        <div class="k-topbar">
            <p class="k-title">
                <a href="{{ url('/') }}">Сайт посвящён И.&nbsp;М.&nbsp;Когану и исследованиям в области парапсихологии</a>
            </p>
            <div class="k-auth">
                @auth
                    {{ auth()->user()->name }}
                    @if (auth()->user()->is_admin)
                        &middot; <a href="{{ url('/admin') }}">Админка</a>
                    @endif
                    &middot;
                    <form action="{{ route('logout') }}" method="POST" style="display:inline">@csrf
                        <button type="submit">Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Войти</a>
                    &middot; <a href="{{ route('register') }}">Регистрация</a>
                @endauth
            </div>
        </div>

        <button class="k-burger" id="k-burger" type="button" aria-expanded="false" aria-controls="k-menu" aria-label="Меню">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <nav class="k-menubar" id="k-menu">
            <table>
                <tr>
                    @foreach ($menuItems as $top)
                        @php $isTopActive = $activeTop !== null && $activeTop->is($top); @endphp
                        <td>
                            <div class="{{ $isTopActive ? 'k-active' : 'k-empty' }}">
                                <a href="{{ $top->url ?: '#' }}">{{ $top->label }}</a>
                            </div>
                        </td>
                        @if (! $loop->last)
                            <td class="k-sep"><img src="{{ asset('images/kogan/razdelitel.gif') }}" alt="" width="9" height="18"></td>
                        @endif
                    @endforeach
                </tr>
            </table>
        </nav>
    </header>

    @if (session('success'))
        <div class="k-flash">{{ session('success') }}</div>
    @endif

    <div class="k-cols">
        @hasSection('sidebar')
            <aside class="k-side k-side-custom">
                @yield('sidebar')
            </aside>
        @elseif ($activeTop !== null && $activeTop->children->isNotEmpty())
            <aside class="k-side">
                <ul>
                    @foreach ($activeTop->children as $child)
                        <li class="{{ $activeChild !== null && $activeChild->is($child) ? 'k-here' : '' }}">
                            <a href="{{ $child->url ?: '#' }}">{{ $child->label }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif

        <div class="k-main">
            <div class="k-frame-top"></div>
            <div class="k-frame-mid">
                <div class="k-frame-left"><img src="{{ asset('images/kogan/top-ll.jpg') }}" alt=""></div>
                <div class="k-content">
                    @if ($currentPath !== '/')
                        <div class="k-crumbs">
                            <a href="{{ url('/') }}">Главная</a>
                            @if ($activeTop !== null)
                                &rarr; @if ($activeChild !== null && rtrim((string) $activeChild->url, '/') !== $currentPath)
                                    <a href="{{ $activeChild->url }}">{{ $activeChild->label }}</a> &rarr;
                                @else
                                    {{ $activeChild?->label ?? $activeTop->label }}
                                @endif
                            @endif
                        </div>
                    @endif

                    @yield('content')
                </div>
                <div class="k-frame-right"><img src="{{ asset('images/kogan/top-r2.jpg') }}" alt=""></div>
            </div>
            <div class="k-frame-bot">
                <div class="k-frame-bot-l"><img src="{{ asset('images/kogan/bot-l.jpg') }}" alt=""></div>
                <div class="k-frame-bot-m"></div>
                <div class="k-frame-bot-r"><img src="{{ asset('images/kogan/bot-r2.jpg') }}" alt=""></div>
            </div>
        </div>
    </div>

    <footer class="k-footer">
        <div class="k-contacts">
            (с) <a href="{{ url('/') }}">Сайт Когана</a> (2008-2014)
        </div>
    </footer>
</div>
</body>
</html>
