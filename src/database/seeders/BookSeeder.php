<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title' => 'Справочник по симптоматологии',
                'author' => 'Иванов И.И.',
                'description' => 'Комплексный справочник симптомов и их проявлений',
                'isbn' => '978-5-00001-001-1',
            ],
            [
                'title' => 'Основы физиотерапии',
                'author' => 'Петров П.П.',
                'description' => 'Учебное пособие по методам физиотерапии',
                'isbn' => '978-5-00001-002-8',
            ],
            [
                'title' => 'Народная медицина: проверенные рецепты',
                'author' => 'Сидорова А.В.',
                'description' => 'Сборник народных рецептов лечения',
                'isbn' => '978-5-00001-003-5',
            ],
            [
                'title' => 'Анатомия человека',
                'author' => 'Козлов С.М.',
                'description' => 'Учебник по анатомии для студентов медвузов',
                'isbn' => '978-5-00001-004-2',
            ],
            [
                'title' => 'Здоровье позвоночника',
                'author' => 'Морозова Е.Н.',
                'description' => 'Практическое руководство по здоровью спины',
                'isbn' => '978-5-00001-005-9',
            ],
        ];

        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
