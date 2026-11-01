<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Book;
use App\Services\IllustrationGenerator;
use Illuminate\Database\Seeder;

class IllustrationSeeder extends Seeder
{
    public function run(IllustrationGenerator $generator): void
    {
        // Обложки для всех книг и баннеры для всех статей (свежие данные).
        Book::query()->chunkById(100, function ($books) use ($generator) {
            foreach ($books as $book) {
                $book->update(['image_path' => $generator->forBook($book)]);
            }
        });

        Article::query()->chunkById(100, function ($articles) use ($generator) {
            foreach ($articles as $article) {
                $article->update(['image_path' => $generator->forArticle($article)]);
            }
        });

        // Декоративные фигуры, используемые в телах сеяных статей.
        for ($i = 1; $i <= 6; $i++) {
            $generator->figure($i);
        }

        $generator->placeholders();
    }
}
