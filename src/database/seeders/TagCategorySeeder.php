<?php

namespace Database\Seeders;

use App\Models\TagCategory;
use Illuminate\Database\Seeder;

class TagCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Часть тела',
                'slug' => 'body_part',
                'color' => '#3b82f6',
                'description' => 'Части человеческого тела',
            ],
            [
                'name' => 'Болезнь/симптом',
                'slug' => 'ailment',
                'color' => '#ef4444',
                'description' => 'Заболевания и симптомы',
            ],
            [
                'name' => 'Лечение',
                'slug' => 'treatment',
                'color' => '#22c55e',
                'description' => 'Методы и средства лечения',
            ],
            [
                'name' => 'Другое',
                'slug' => 'other',
                'color' => '#6b7280',
                'description' => 'Прочие категории',
            ],
        ];

        foreach ($categories as $category) {
            TagCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
