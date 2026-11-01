<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('quoteWord')) {
    /**
     * Склонение слова "цитата" для русского языка.
     *
     * @param  int  $count  Количество
     * @return string Склонённое слово
     */
    function quoteWord(int $count): string
    {
        $n = abs($count) % 100;
        if ($n >= 11 && $n <= 19) {
            return 'цитат';
        }
        $n %= 10;
        if ($n === 1) {
            return 'цитата';
        }
        if ($n >= 2 && $n <= 4) {
            return 'цитаты';
        }

        return 'цитат';
    }
}

if (! function_exists('storage_image')) {
    /**
     * URL картинки из public-диска или плейсхолдер, если путь не задан
     * или файл физически отсутствует (например, потерян при переносе).
     */
    function storage_image(?string $path, string $fallback = 'images/placeholder-cover.svg'): string
    {
        if ($path !== null && $path !== '' && Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return asset($fallback);
    }
}

if (! function_exists('slugify')) {
    /**
     * Slug из строки с транслитерацией кириллицы (без зависимости от ext-intl).
     */
    function slugify(string $value): string
    {
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $value = mb_strtolower(trim($value));
        $value = strtr($value, $translit);

        return Str::slug($value);
    }
}
