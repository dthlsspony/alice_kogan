<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Book;
use Illuminate\Support\Facades\Storage;

/**
 * Генератор векторных иллюстраций: обложки книг (2:3), баннеры статей
 * (широкие), декоративные фигуры для вставки в текст и плейсхолдеры.
 * Детерминирован: палитра и узоры выбираются по crc32 ключа, поэтому
 * повторный запуск даёт ту же картинку.
 */
class IllustrationGenerator
{
    /** @var array<int, array{0: string, 1: string, 2: string}> [начало, конец градиента, акцент] */
    private const PALETTES = [
        ['#4f46e5', '#7c3aed', '#c7d2fe'], // indigo → violet
        ['#0f766e', '#14b8a6', '#99f6e4'], // teal
        ['#b45309', '#f59e0b', '#fde68a'], // amber
        ['#be123c', '#f43f5e', '#fecdd3'], // rose
        ['#1d4ed8', '#0ea5e9', '#bae6fd'], // blue
        ['#4d7c0f', '#84cc16', '#d9f99d'], // lime
        ['#6d28d9', '#a78bfa', '#ede9fe'], // violet
        ['#0f172a', '#475569', '#cbd5e1'], // slate
    ];

    /**
     * Обложка книги (600×900) → относительный путь на диске public.
     */
    public function forBook(Book $book): string
    {
        $path = "books/covers/book-{$book->id}.svg";
        Storage::disk('public')->put($path, $this->coverSvg($book->title, $book->author, "book-{$book->id}"));

        return $path;
    }

    /**
     * Баннер статьи (1200×450) → относительный путь.
     */
    public function forArticle(Article $article): string
    {
        $path = "articles/banners/{$article->slug}.svg";
        Storage::disk('public')->put($path, $this->bannerSvg("article-{$article->id}"));

        return $path;
    }

    /**
     * Декоративная фигура для вставки в текст статьи (800×400).
     */
    public function figure(int $number): string
    {
        $path = "articles/figures/figure-{$number}.svg";
        Storage::disk('public')->put($path, $this->figureSvg("figure-{$number}"));

        return $path;
    }

    /**
     * Плейсхолдеры в public/images (используются, когда картинка не задана).
     */
    public function placeholders(): void
    {
        $dir = public_path('images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($dir.'/placeholder-cover.svg', $this->coverSvg('Коллекция цитат', 'Alice', 'placeholder-cover'));
        file_put_contents($dir.'/placeholder-banner.svg', $this->bannerSvg('placeholder-banner'));
    }

    public function coverSvg(string $title, string $author, string $seedKey): string
    {
        [$c1, $c2, $accent] = $this->palette($seedKey);
        $seed = crc32($seedKey);
        $lines = $this->wrapText($title, 14);
        $lineHeight = 52;
        $startY = 430 - (count($lines) - 1) * $lineHeight / 2;
        $textLines = collect($lines)
            ->map(fn ($line, $i) => '<text x="300" y="'.($startY + $i * $lineHeight).'" text-anchor="middle" '
                .'font-family="Georgia, \'Times New Roman\', serif" font-size="44" font-weight="bold" '
                .'fill="#ffffff">'.htmlspecialchars($line, ENT_XML1).'</text>')
            ->implode("\n");

        $decor = $this->decor($seed, $c1, $c2, $accent, 600, 900);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900" role="img">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="600" height="900" fill="url(#bg)"/>
  {$decor}
  <rect x="0" y="0" width="600" height="900" fill="none"/>
  <line x1="230" y1="300" x2="370" y2="300" stroke="{$accent}" stroke-width="3" opacity="0.9"/>
  {$textLines}
  <text x="300" y="820" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="28" fill="#ffffff" opacity="0.92">{$this->escape($author)}</text>
  <rect x="16" y="16" width="568" height="868" fill="none" stroke="#ffffff" stroke-opacity="0.35" stroke-width="2" rx="10"/>
</svg>
SVG;
    }

    public function bannerSvg(string $seedKey): string
    {
        [$c1, $c2, $accent] = $this->palette($seedKey);
        $decor = $this->decor(crc32($seedKey), $c1, $c2, $accent, 1200, 450);
        $qx = 1080;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="450" viewBox="0 0 1200 450" role="img">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="450" fill="url(#bg)"/>
  {$decor}
  <text x="{$qx}" y="330" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="260" font-weight="bold" fill="#ffffff" opacity="0.22">&#171;</text>
</svg>
SVG;
    }

    public function figureSvg(string $seedKey): string
    {
        [$c1, $c2, $accent] = $this->palette($seedKey);
        $seed = crc32($seedKey);
        $variant = $seed % 3;
        $decor = match ($variant) {
            0 => $this->wavesDecor($accent, 800, 400),
            1 => $this->circlesDecor($seed, $accent, 800, 400),
            default => $this->gridDecor($accent, 800, 400),
        };

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400" role="img">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="400" rx="16" fill="url(#bg)"/>
  {$decor}
</svg>
SVG;
    }

    /**
     * Случайные (детерминированные) декоративные элементы.
     */
    private function decor(int $seed, string $c1, string $c2, string $accent, int $w, int $h): string
    {
        mt_srand($seed);
        $parts = [];

        // Крупные полупрозрачные кольца.
        for ($i = 0; $i < 4; $i++) {
            $cx = mt_rand(0, $w);
            $cy = mt_rand(0, $h);
            $r = mt_rand((int) ($w * 0.08), (int) ($w * 0.3));
            $parts[] = '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="none" stroke="#ffffff" stroke-opacity="0.14" stroke-width="'.mt_rand(2, 5).'"/>';
        }

        // Заполненные круги с прозрачностью.
        for ($i = 0; $i < 3; $i++) {
            $cx = mt_rand(0, $w);
            $cy = mt_rand(0, $h);
            $r = mt_rand((int) ($w * 0.04), (int) ($w * 0.12));
            $parts[] = '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="'.$accent.'" opacity="0.18"/>';
        }

        // Диагональные штрихи.
        for ($i = 0; $i < 6; $i++) {
            $x = mt_rand(0, $w);
            $y = mt_rand(0, $h);
            $len = mt_rand(30, 110);
            $parts[] = '<line x1="'.$x.'" y1="'.$y.'" x2="'.($x + $len).'" y2="'.($y - $len).'" stroke="#ffffff" stroke-opacity="0.12" stroke-width="2"/>';
        }

        return implode("\n  ", $parts);
    }

    private function wavesDecor(string $accent, int $w, int $h): string
    {
        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            $y = 80 + $i * 80;
            $opacity = 0.25 - $i * 0.04;
            $parts[] = '<path d="M0 '.$y.' C '.($w * 0.25).' '.($y - 50).', '.($w * 0.5).' '.($y + 50).', '.$w.' '.$y.'" fill="none" stroke="'.$accent.'" stroke-opacity="'.$opacity + 0.1.'" stroke-width="6"/>';
        }

        return implode("\n  ", $parts);
    }

    private function circlesDecor(int $seed, string $accent, int $w, int $h): string
    {
        mt_srand($seed + 7);
        $parts = [];
        for ($i = 0; $i < 9; $i++) {
            $cx = mt_rand(40, $w - 40);
            $cy = mt_rand(40, $h - 40);
            $r = mt_rand(16, 90);
            $parts[] = '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="'.$accent.'" opacity="'.(mt_rand(12, 35) / 100).'"/>';
        }

        return implode("\n  ", $parts);
    }

    private function gridDecor(string $accent, int $w, int $h): string
    {
        $parts = [];
        for ($x = 60; $x < $w; $x += 90) {
            for ($y = 50; $y < $h; $y += 90) {
                $size = 10 + (($x + $y) % 40);
                $parts[] = '<rect x="'.($x - $size / 2).'" y="'.($y - $size / 2).'" width="'.$size.'" height="'.$size.'" rx="6" fill="'.$accent.'" opacity="0.3" transform="rotate(45 '.$x.' '.$y.')"/>';
            }
        }

        return implode("\n  ", $parts);
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function palette(string $seedKey): array
    {
        return self::PALETTES[crc32($seedKey) % count(self::PALETTES)];
    }

    /** @return list<string> */
    private function wrapText(string $text, int $maxChars): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 4); // не более 4 строк на обложке
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1);
    }
}
