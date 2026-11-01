<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\Tag;
use App\Models\TagCategory;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // Map category slugs to IDs
        $categoryMap = TagCategory::pluck('id', 'slug')->toArray();

        $tags = [
            // Body part tags
            ['name' => 'голова', 'slug' => 'голова', 'category_id' => $categoryMap['body_part']],
            ['name' => 'спина', 'slug' => 'спина', 'category_id' => $categoryMap['body_part']],
            ['name' => 'поясница', 'slug' => 'поясница', 'category_id' => $categoryMap['body_part']],
            ['name' => 'суставы', 'slug' => 'суставы', 'category_id' => $categoryMap['body_part']],
            ['name' => 'горло', 'slug' => 'горло', 'category_id' => $categoryMap['body_part']],
            ['name' => 'желудок', 'slug' => 'желудок', 'category_id' => $categoryMap['body_part']],
            ['name' => 'позвоночник', 'slug' => 'позвоночник', 'category_id' => $categoryMap['body_part']],
            ['name' => 'лёгкие', 'slug' => 'лёгкие', 'category_id' => $categoryMap['body_part']],
            ['name' => 'сердце', 'slug' => 'сердце', 'category_id' => $categoryMap['body_part']],

            // Ailment tags
            ['name' => 'головная_боль', 'slug' => 'головная_боль', 'category_id' => $categoryMap['ailment']],
            ['name' => 'мигрень', 'slug' => 'мигрень', 'category_id' => $categoryMap['ailment']],
            ['name' => 'артрит', 'slug' => 'артрит', 'category_id' => $categoryMap['ailment']],
            ['name' => 'простуда', 'slug' => 'простуда', 'category_id' => $categoryMap['ailment']],
            ['name' => 'кашель', 'slug' => 'кашель', 'category_id' => $categoryMap['ailment']],
            ['name' => 'остеохондроз', 'slug' => 'остеохондроз', 'category_id' => $categoryMap['ailment']],
            ['name' => 'грыжа_диска', 'slug' => 'грыжа_диска', 'category_id' => $categoryMap['ailment']],
            ['name' => 'воспаление', 'slug' => 'воспаление', 'category_id' => $categoryMap['ailment']],
            ['name' => 'температура', 'slug' => 'температура', 'category_id' => $categoryMap['ailment']],
            ['name' => 'тошнота', 'slug' => 'тошнота', 'category_id' => $categoryMap['ailment']],
            ['name' => 'спазм', 'slug' => 'спазм', 'category_id' => $categoryMap['ailment']],
        ];

        $tagMap = [];
        foreach ($tags as $tag) {
            $tagMap[$tag['name']] = Tag::create($tag);
        }

        // Attach tags to quotes
        $tagAssociations = [
            // Book 1: Справочник по симптоматологии
            1 => ['голова', 'головная_боль'],
            2 => ['голова', 'мигрень', 'головная_боль'],
            3 => ['спина', 'поясница', 'воспаление'],
            4 => ['желудок', 'тошнота'],
            5 => ['температура', 'воспаление'],

            // Book 2: Основы физиотерапии
            6 => ['спина', 'суставы', 'спазм'],
            7 => ['спазм', 'воспаление'],
            8 => ['воспаление'],
            9 => ['суставы', 'артрит'],
            10 => ['воспаление'],

            // Book 3: Народная медицина
            11 => ['горло', 'воспаление', 'кашель'],
            12 => ['кашель', 'простуда'],
            13 => ['воспаление', 'спазм'],
            14 => ['суставы', 'артрит'],

            // Book 4: Анатомия человека
            15 => ['позвоночник'],
            16 => ['сердце'],
            17 => ['лёгкие'],
            18 => ['желудок'],

            // Book 5: Здоровье позвоночника
            19 => ['спина', 'позвоночник'],
            20 => ['спина', 'поясница', 'позвоночник', 'остеохондроз'],
            21 => ['позвоночник', 'спина'],
            22 => ['позвоночник', 'грыжа_диска', 'спина'],
            23 => ['позвоночник', 'спина'],
        ];

        foreach ($tagAssociations as $quoteId => $tagNames) {
            $quote = Quote::find($quoteId);
            if ($quote) {
                $tagIds = array_map(fn ($name) => $tagMap[$name]->id, $tagNames);
                $quote->tags()->attach($tagIds);
            }
        }
    }
}
