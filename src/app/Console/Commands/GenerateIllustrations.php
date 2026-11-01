<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Book;
use App\Services\IllustrationGenerator;
use Illuminate\Console\Command;

class GenerateIllustrations extends Command
{
    protected $signature = 'illustrations:generate
        {--only= : books|articles|figures|placeholders — сгенерировать только часть}
        {--refresh : Перегенерировать, даже если путь уже задан}';

    protected $description = 'Генерирует SVG-обложки книг, баннеры статей, декоративные фигуры и плейсхолдеры';

    public function handle(IllustrationGenerator $generator): int
    {
        $only = (string) ($this->option('only') ?: '');
        $refresh = (bool) $this->option('refresh');
        $want = fn (string $part) => $only === '' || $only === $part;

        if ($want('placeholders')) {
            $generator->placeholders();
            $this->components->info('Плейсхолдеры записаны в public/images.');
        }

        if ($want('books')) {
            $count = 0;
            Book::query()->chunkById(100, function ($books) use ($generator, $refresh, &$count) {
                foreach ($books as $book) {
                    if ($refresh || $book->image_path === null) {
                        $book->update(['image_path' => $generator->forBook($book)]);
                        $count++;
                    }
                }
            });
            $this->components->info("Обложки книг: сгенерировано {$count}.");
        }

        if ($want('articles')) {
            $count = 0;
            Article::query()->chunkById(100, function ($articles) use ($generator, $refresh, &$count) {
                foreach ($articles as $article) {
                    if ($refresh || $article->image_path === null) {
                        $article->update(['image_path' => $generator->forArticle($article)]);
                        $count++;
                    }
                }
            });
            $this->components->info("Баннеры статей: сгенерировано {$count}.");
        }

        if ($want('figures')) {
            $count = 0;
            for ($i = 1; $i <= 6; $i++) {
                $generator->figure($i);
                $count++;
            }
            $this->components->info("Декоративные фигуры: сгенерировано {$count}.");
        }

        return self::SUCCESS;
    }
}
