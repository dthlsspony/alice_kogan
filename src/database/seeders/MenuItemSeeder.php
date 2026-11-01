<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            ['label' => 'Главная', 'url' => '/', 'sort_order' => 1, 'children' => []],
            ['label' => 'Библиография', 'url' => '/books', 'sort_order' => 2, 'children' => []],
            ['label' => 'Статьи', 'url' => '/articles', 'sort_order' => 3, 'children' => []],
            [
                'label' => 'О сайте',
                'url' => '#',
                'sort_order' => 4,
                'children' => [
                    ['label' => 'Об авторе', 'url' => '/about/author', 'sort_order' => 1],
                    ['label' => 'О проекте', 'url' => '/about/project', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($roots as $rootData) {
            $children = $rootData['children'];
            unset($rootData['children']);

            $root = MenuItem::firstOrCreate(
                ['label' => $rootData['label']],
                $rootData + ['is_visible' => true, 'target' => '_self'],
            );

            foreach ($children as $childData) {
                MenuItem::firstOrCreate(
                    ['label' => $childData['label']],
                    $childData + ['parent_id' => $root->id, 'is_visible' => true, 'target' => '_self'],
                );
            }
        }
    }
}
