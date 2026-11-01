<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Одноразовый импортёр старого сайта kogan-im.com (рабочая копия в
 * kogan_new/legacy, cp1251) в дамп для KoganContentSeeder.
 *
 * Страницы режутся по маркерам require(boxbefore/boxafter), контент
 * конвертируется в UTF-8, ссылки на картинки/документы переписываются
 * на /storage/kogan/..., сами файлы копируются в public-диск.
 *
 * Результат: database/seeders/data/kogan/{articles,pages,books,assets}.json
 */
class KoganImport extends Command
{
    protected $signature = 'kogan:import
        {--src=/var/www/legacy : рабочая копия старого сайта}
        {--dry : ничего не записывать, только отчёт}';

    protected $description = 'Импорт контента kogan-im.com в дамп для сеялки (статьи, страницы, книги, иллюстрации)';

    /** Секции старого сайта, подлежащие переносу (остальное — мусор). */
    private const SECTIONS = [
        'news', 'about', 'books', 'investigation', 'seminars',
        'conf', 'biol', 'mntores', 'links', 'contacts',
    ];

    /** Фрагменты, входящие в другие страницы через require, — отдельно не переносим. */
    private const FRAGMENTS = ['news/news_events.php', 'news/news_sait.php'];

    private const HOST = 'http://www.kogan-im.com';

    /** @var array<string, string> старый путь без .php → слаг страницы */
    private array $pageSlugs = [];

    /**  array<string, string> старый путь без .php → слаг статьи */
    private array $articleSlugs = [];

    private string $src;

    private string $out;

    /** @var array<string, true> относительные пути скопированных файлов */
    private array $assets = [];

    /** @var array<string, string> битые ссылки: путь => страница */
    private array $broken = [];

    /** @var array<string, true> занятые слаги статей */
    private array $usedSlugs = [];

    public function handle(): int
    {
        $this->src = rtrim((string) $this->option('src'), '/');
        $this->out = base_path('database/seeders/data/kogan');

        if (! is_dir($this->src)) {
            $this->error("Рабочая копия не найдена: {$this->src}");

            return self::FAILURE;
        }

        $pages = [];
        $articles = [];
        $rels = $this->collectPageFiles();

        // Картинка-e-mail с главной страницы старого сайта (index.php в copy
        // не входил) — регистрируем руками, она нужна новому главному виду.
        if (is_file("{$this->src}/contacts/emails/email_larissa.gif")) {
            $this->assets['contacts/emails/email_larissa.gif'] = true;
        }

        // Первый проход: классификация и слаги — нужны ДО парсинга, чтобы
        // перелинковка видела и страницы, и статьи (карта «адрес → адрес»).
        $kinds = [];

        foreach ($rels as $rel) {
            $html = file_get_contents("{$this->src}/{$rel}");
            $path = self::stripPhp($rel);

            if ($this->isPageCandidate($rel, $html)) {
                $slug = $this->pageSlug($rel);
                $this->pageSlugs[$path] = $slug;

                // Корень секции: ссылка вида «/investigation» → index-страница.
                if (str_ends_with($path, '/index')) {
                    $this->pageSlugs[substr($path, 0, -strlen('/index'))] = $slug;
                }
            } else {
                $this->articleSlugs[$path] = $this->articleSlug($rel);
            }

            $kinds[$rel] = $this->isPageCandidate($rel, $html);
        }

        foreach ($rels as $rel) {
            $record = $this->parsePage($rel);

            if ($record === null) {
                $this->warn("Пропущена: {$rel}");

                continue;
            }

            if ($kinds[$rel]) {
                $record['slug'] = $this->pageSlug($rel);
                $pages[] = $record;
            } else {
                $record['slug'] = $this->articleSlugs[self::stripPhp($rel)];
                $record['section'] = strtok((string) $record['legacy_url'], '/') ?: 'kogan';
                $articles[] = $record;
            }
        }

        $this->augmentPages($pages);

        $books = $this->extractBooks($pages);

        $this->info(sprintf(
            'Разобрано: %d статей, %d страниц, %d книг, %d файлов, %d битых ссылок',
            count($articles), count($pages), count($books), count($this->assets), count($this->broken)
        ));

        if ($this->option('dry')) {
            return self::SUCCESS;
        }

        if (! is_dir($this->out)) {
            mkdir($this->out, 0755, true);
        }

        $this->copyAssets();
        $this->dump('articles.json', $articles);
        $this->dump('pages.json', $pages);
        $this->dump('books.json', $books);
        $this->dump('assets.json', array_keys($this->assets));

        if ($this->broken !== []) {
            $this->dump('broken.json', array_map(
                fn (string $page, string $path) => ['path' => $path, 'page' => $page],
                array_values($this->broken), array_keys($this->broken)
            ));
        }

        $this->info("Дамп готов: {$this->out}");

        return self::SUCCESS;
    }

    /** Все контентные страницы (маркер boxbefore) во всех секциях. */
    private function collectPageFiles(): array
    {
        $files = [];

        foreach (self::SECTIONS as $section) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    "{$this->src}/{$section}",
                    \FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $rel = "{$section}/".ltrim(str_replace('\\', '/', substr(
                    $file->getPathname(), strlen("{$this->src}/{$section}/")
                )), '/');

                if (in_array($rel, self::FRAGMENTS, true)) {
                    continue;
                }

                // Служебные файлы гостевых книг — мусор.
                if (str_starts_with($rel, 'contacts/data/') || str_starts_with($rel, 'contacts/guestbook/')) {
                    continue;
                }

                if (str_contains(@file_get_contents($file->getPathname()) ?: '', 'boxbefore')) {
                    $files[] = $rel;
                }
            }
        }

        sort($files);

        return $files;
    }

    /** Разбор одной страницы: кодировка, вырезание контента, ссылки, метаданные. */
    private function parsePage(string $rel): ?array
    {
        $html = $this->readLegacy($rel);
        $content = $this->cutContent($html);

        if ($content === null) {
            return null;
        }

        $content = $this->inlineRequires($content, dirname($rel));
        $content = $this->normalizeUrls($content, dirname($rel));
        $content = $this->modernizeMedia($content);

        // Остатки PHP-вставок, head-элементов внутри контента и пустые строки.
        $content = preg_replace('/<\?php.*?\?>/s', '', $content) ?? $content;
        $content = preg_replace('/<\? .*?\?>/s', '', $content) ?? $content;
        $content = preg_replace('#<(link|meta)\b[^>]*>#i', '', $content) ?? $content;
        $content = trim(preg_replace("/\n{3,}/", "\n\n", $content) ?? $content);

        $imagePath = null;
        if (preg_match('#<img[^>]+src="/storage/kogan/([^"]+)"#i', $content, $m)) {
            $imagePath = 'kogan/'.rawurldecode($m[1]);
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8')) ?? '');

        return [
            'title' => $this->extractTitle($html, $rel),
            'slug' => '',
            'is_page' => $this->isPageCandidate($rel, $html),
            'excerpt' => mb_substr($text, 0, 250),
            'body' => $content,
            'image_path' => $imagePath,
            'published_at' => $this->isPageCandidate($rel, $html) ? null : $this->guessDate($html, $rel),
            'legacy_url' => $rel,
        ];
    }

    private function readLegacy(string $rel): string
    {
        $raw = file_get_contents("{$this->src}/{$rel}");

        return mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
    }

    /** Контент лежит между require(boxbefore.php) и require(boxafter.php). */
    private function cutContent(string $html): ?string
    {
        $marker = '#<\?php?\s*(?:require|include)\s*\(?\s*["\'][^"\']*(boxbefore|boxafter)\.php["\']\s*\)?\s*;?\s*\?>#i';

        if (! preg_match_all($marker, $html, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = null;
        $end = null;

        foreach ($m[1] as $i => [$name, $offset]) {
            if (strtolower($name) === 'boxbefore') {
                $start = $m[0][$i][1] + strlen($m[0][$i][0]);
            } elseif (strtolower($name) === 'boxafter' && $end === null) {
                $end = $m[0][$i][1];
            }
        }

        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        return trim(substr($html, $start, $end - $start));
    }

    /** Встраиваем вложенные фрагменты (news_events.php и т.п.). */
    private function inlineRequires(string $content, string $dir): string
    {
        for ($i = 0; $i < 5; $i++) {
            $content = preg_replace_callback(
                '#<\?php\s*(?:require|include)\s*\(?\s*["\']([^"\']+)["\']\s*\)?\s*;?\s*\?>#i',
                function (array $m) use ($dir): string {
                    $rel = self::normalizeRelative("{$dir}/".basename($m[1]));

                    if (str_contains($m[1], 'box') || ! is_file("{$this->src}/{$rel}")) {
                        return $m[0];
                    }

                    return $this->readLegacy($rel);
                },
                $content
            ) ?? $content;

            if (! preg_match('#<\?php\s*(?:require|include)#i', $content)) {
                break;
            }
        }

        return $content;
    }

    /**
     * Переписываем ссылки: $picpath/$sitepath/абсолютные URL старого хоста —
     * в корневые пути; существующие файлы — в /storage/kogan/...; ссылки на
     * .php-страницы — в адреса новых страниц (без .php).
     */
    private function normalizeUrls(string $content, string $dir): string
    {
        // Вариант «echo "$sitepath/путь";»: путь целиком внутри PHP-вставки.
        $content = preg_replace_callback(
            '/<\?php\s*echo\s*"([^"]*)";?\s*\?>/',
            fn (array $m): string => str_replace(
                ['$sitepath', '$picpath', '$host_name'],
                [self::HOST, self::HOST.'/images/', self::HOST],
                $m[1]
            ),
            $content
        ) ?? $content;

        $content = preg_replace("/<\?php\s*echo\s+\\\$picpath\s*;?\?>\/?/i", self::HOST.'/images/', $content) ?? $content;
        $content = preg_replace("/<\?php\s*echo\s+\\\$(sitepath|host_name)\s*;?\?>\/?/i", self::HOST, $content) ?? $content;
        $content = str_replace(self::HOST.'/', '/', $content);

        // Хост без завершающего слэша: склейка «echo $host_name» + «investigation/…».
        $content = preg_replace('~'.preg_quote(self::HOST, '~').'(?!/)~i', '/', $content) ?? $content;

        return preg_replace_callback(
            '#(href|src)=["\']([^"\']+)["\']#i',
            function (array $m) use ($dir): string {
                [$attr, $url] = [$m[1], $m[2]];

                if (preg_match('~^(https?:|//|mailto:|javascript:|#|data:)~i', $url)) {
                    return $m[0];
                }

                $path = self::normalizeRelative(str_starts_with($url, '/')
                    ? ltrim($url, '/')
                    : "{$dir}/{$url}");

                // Ссылка на страницу или статью старого сайта → новый адрес.
                $noPhp = self::stripPhp($path);

                if (isset($this->pageSlugs[$noPhp])) {
                    return "{$attr}=\"/{$this->pageSlugs[$noPhp]}\"";
                }

                if (isset($this->articleSlugs[$noPhp])) {
                    return "{$attr}=\"/articles/{$this->articleSlugs[$noPhp]}\"";
                }

                if (is_file("{$this->src}/{$path}")) {
                    $this->assets[$path] = true;

                    return "{$attr}=\"/storage/kogan/".self::encodeUrlPath($path).'"';
                }

                // Файл «сдвинут» на уровень выше из-за кривых путей на старом
                // сайте (conf/2010/2010/... → conf/2010/...).
                $upOne = preg_replace('#/[^/]+/([^/]+)$#', '/$1', $path);

                if ($upOne !== $path && is_file("{$this->src}/{$upOne}")) {
                    $this->assets[$upOne] = true;

                    return "{$attr}=\"/storage/kogan/".self::encodeUrlPath($upOne).'"';
                }

                if (preg_match('/\.(jpe?g|gif|png|svg|pdf|doc|docx|rtf|wmv|mp4)$/i', $path)) {
                    $this->broken[$path] = $dir;

                    return "{$attr}=\"/storage/kogan/".self::encodeUrlPath($path).'"';
                }

                // Похоже на страницу, которой нет ни там, ни там (letter01...).
                $this->broken[$path] = $dir;

                return "{$attr}=\"/{$noPhp}\"";
            },
            $content
        ) ?? $content;
    }

    /** Заголовок: снимаем приставку «И.М. Коган:», при пустом — из имени файла. */
    private function extractTitle(string $html, string $rel): string
    {
        $title = '';

        if (preg_match('#<title>([^<]*)</title>#i', $html, $m)) {
            $title = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        $title = preg_replace('/^И\.\s*М\.\s*Коган\s*:\s*/iu', '', $title) ?? $title;
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');

        return $title !== '' ? $title : ucfirst(pathinfo($rel, PATHINFO_FILENAME));
    }

    /**
     * Дата публикации из имени файла/подсекции: 19881029.php, 2000_01.php,
     * 1999.php, 198810rabotnitsa.php. Дату распарсить не удалось — берём
     * 2008-01-01 (год открытия сайта), иначе статья не попадёт в published.
     */
    private function guessDate(string $html, string $rel): string
    {
        $name = pathinfo($rel, PATHINFO_FILENAME);

        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $name, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        if (preg_match('/^(\d{4})(\d{2})(?!\d)/', $name, $m)) {
            return "{$m[1]}-{$m[2]}-01";
        }

        if (preg_match('/^(\d{4})[_-](\d{2})/', $name, $m)) {
            return "{$m[1]}-{$m[2]}-01";
        }

        if (preg_match('#/(\d{4})(?:\.php|/)#', $rel, $m)) {
            return "{$m[1]}-01-01";
        }

        if (preg_match('/\$thisPage2="(\d{4})"/', $html, $m)) {
            return "{$m[1]}-01-01";
        }

        return '2008-01-01';
    }

    /** Статические разделы и обзорные index-страницы переносим как pages. */
    private function isPageCandidate(string $rel, string $html): bool
    {
        $section = strtok($rel, '/');
        $basename = basename($rel);

        if (in_array($section, ['about', 'biol', 'contacts', 'links'], true)) {
            return true;
        }

        if ($section === 'books') {
            // Листовые страницы библиотеки ($thisPage3) — газетные статьи.
            return ! str_contains($html, '$thisPage3=');
        }

        return $basename === 'index.php';
    }

    /** Слаг страницы = старый адрес без .php (вложенность сохраняем). */
    private function pageSlug(string $rel): string
    {
        $slug = self::normalizeRelative(preg_replace('/\.php$/i', '', $rel));

        if (str_ends_with($slug, '/index')) {
            $slug = substr($slug, 0, -strlen('/index'));
        }

        return match ($slug) {
            'books' => 'biblioteka',
            'books/books' => 'biblioteka/knigi',
            'books/articles' => 'biblioteka/stati',
            default => $slug,
        };
    }

    /** Слаг статьи — из имени файла, при коллизии — с префиксом секции. */
    private function articleSlug(string $rel): string
    {
        $base = pathinfo($rel, PATHINFO_FILENAME);
        $section = strtok($rel, '/');

        $slug = slugify($base);

        if (isset($this->usedSlugs[$slug])) {
            $slug = slugify("{$section}-{$base}");
        }

        while (isset($this->usedSlugs[$slug])) {
            $slug = preg_match('/-\d+$/', $slug)
                ? preg_replace('/-\d+$/', '', $slug).'-'.((int) preg_replace('/^.*-(\d+)$/', '$1', $slug) + 1)
                : $slug.'-2';
        }

        return $this->usedSlugs[$slug] = $slug;
    }

    /**
     * Медиа XXI века: Flash-плеер YouTube → iframe, ссылки .wmv → .mp4
     * (конвертированные копии лежат рядом с исходниками в legacy).
     */
    private function modernizeMedia(string $content): string
    {
        $content = preg_replace_callback(
            '#<object[^>]*>.*?</object>#is',
            function (array $m): string {
                if (! preg_match('#youtube\.com/v/([A-Za-z0-9_-]{6,})#i', $m[0], $id)) {
                    return $m[0];
                }

                return '<iframe class="k-video" width="425" height="344" '
                    .'src="https://www.youtube-nocookie.com/embed/'.$id[1].'" '
                    .'title="Видео YouTube" allowfullscreen></iframe>';
            },
            $content
        ) ?? $content;

        // Осторожный <embed> вне <object> (на всякий случай).
        $content = preg_replace_callback(
            '#<embed[^>]+src="[^"]*youtube\.com/v/([A-Za-z0-9_-]{6,})[^"]*"[^>]*>(?:</embed>)?#i',
            fn (array $m): string => '<iframe class="k-video" width="425" height="344" '
                .'src="https://www.youtube-nocookie.com/embed/'.$m[1].'" '
                .'title="Видео YouTube" allowfullscreen></iframe>',
            $content
        ) ?? $content;

        return preg_replace_callback(
            '#href="(/storage/kogan/([^"]+))\.wmv"#i',
            function (array $m): string {
                $mp4 = $m[2].'.mp4';

                if (! is_file("{$this->src}/{$mp4}")) {
                    return $m[0];
                }

                // mp4 заменяет wmv и в storage, и в ссылке.
                $this->assets[$mp4] = true;
                unset($this->assets[$m[2].'.wmv']);

                return 'href="/storage/kogan/'.self::encodeUrlPath($mp4).'"';
            },
            str_replace('(формат .wmv)', '(формат .mp4)', $content)
        ) ?? $content;
    }

    /**
     * Контент, которого не было в старых php-файлах, но который нужно
     * вернуть на страницы: видео о системе БИОЛ (файл-сирота biol_xvid.avi)
     * и фотовалерею семинара 20.11.2008 (альбом mcGallery photo/albums/1).
     */
    private function augmentPages(array &$pages): void
    {
        foreach ($pages as &$page) {
            if ($page['slug'] === 'biol') {
                // На старом сайте под YouTube-плеером была ссылка на скачивание
                // biol_xvid.avi; в рабочей копии от неё остался плейсхолдер
                // «_ссылка_». Возвращаем ссылку на конвертированный mp4.
                $this->assets['biol/video/biol_xvid.mp4'] = true;
                $page['body'] = preg_replace(
                    '#Также видео можно скачать здесь: _ссылка_<br>\s*Для просмотра и прослушивания нужен кодак xvid#u',
                    'Также видео можно скачать <a href="/storage/kogan/biol/video/biol_xvid.mp4">здесь</a> (формат .mp4)',
                    $page['body']
                ) ?? $page['body'];
            }

            if ($page['slug'] === 'seminars/20091120') {
                $page['body'] .= $this->photoGallery(
                    'photo/albums/1',
                    'Фотографии с семинара (20 ноября 2008 г.)',
                );
            }
        }
    }

    /** Простая сетка миниатюр со ссылками на полные фото, в духе старого сайта. */
    private function photoGallery(string $dir, string $title): string
    {
        $cells = [];

        foreach (glob("{$this->src}/{$dir}/small/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE) ?: [] as $thumb) {
            $full = "{$this->src}/{$dir}/".basename($thumb);

            if (! is_file($full)) {
                continue;
            }

            $this->assets["{$dir}/".basename($full)] = true;
            $this->assets["{$dir}/small/".basename($thumb)] = true;

            $thumbUrl = self::encodeUrlPath("{$dir}/small/".basename($thumb));
            $fullUrl = self::encodeUrlPath("{$dir}/".basename($full));

            $cells[] = '<td><a href="/storage/kogan/'.$fullUrl.'" target="_blank">'
                .'<img src="/storage/kogan/'.$thumbUrl.'" alt="'.htmlspecialchars($title).'" border="0"></a></td>';
        }

        if ($cells === []) {
            return '';
        }

        $rows = array_chunk($cells, 3);
        $html = "\n<hr>\n<p class=\"mtitle\">&bull; ".htmlspecialchars($title).":</p>\n<table class=\"k-gallery\" cellspacing=\"5\">";

        foreach ($rows as $row) {
            $html .= '<tr>'.implode('', $row).'</tr>';
        }

        return $html."</table>\n";
    }

    /** Книги — из списка на books/books.php (уже переписанной страницы biblioteka/knigi). */
    private function extractBooks(array $pages): array
    {
        $body = null;

        foreach ($pages as $page) {
            if ($page['slug'] === 'biblioteka/knigi') {
                $body = $page['body'];
            }
        }

        if ($body === null) {
            return [];
        }

        $books = [];

        if (! preg_match_all(
            '#<a href="(/storage/kogan/[^"]+\.(?:doc|rtf|pdf))"[^>]*>(.*?)</a>(.*?)(?=<a href|<hr|$)#is',
            $body, $m, PREG_SET_ORDER
        )) {
            return $books;
        }

        foreach ($m as $match) {
            $title = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8'));
            $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

            if ($title === '' || preg_match('/скан|скачать|отзыв/ui', $title)) {
                continue;
            }

            $author = 'И. М. Коган';

            if (str_contains($title, ':')) {
                [$maybeAuthor, $rest] = explode(':', $title, 2);

                if (mb_strlen($maybeAuthor) <= 40) {
                    $author = trim($maybeAuthor);
                    $title = trim($rest);
                }
            }

            $desc = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES, 'UTF-8'));
            $desc = preg_replace('/\s+/u', ' ', $desc) ?? $desc;

            $books[] = [
                'title' => $title,
                'author' => $author,
                'description' => mb_substr($desc, 0, 1000),
            ];
        }

        return $books;
    }

    private function copyAssets(): void
    {
        $disk = Storage::disk('public');

        foreach (array_keys($this->assets) as $path) {
            $disk->put("kogan/{$path}", file_get_contents("{$this->src}/{$path}"));
        }
    }

    private function dump(string $name, array $data): void
    {
        file_put_contents(
            "{$this->out}/{$name}",
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private static function stripPhp(string $path): string
    {
        return (string) preg_replace('/\.php$/i', '', $path);
    }

    private static function normalizeRelative(string $path): string
    {
        $parts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($parts);
            } else {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
    }

    private static function encodeUrlPath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
