<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Book;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Контент старого сайта kogan-im.com из дампа kogan:import
 * (database/seeders/data/kogan/*.json). Иллюстрации и документы сеялка
 * кладёт в public-диск (storage/app/public/kogan/...); если их там нет
 * и доступна рабочая копия /var/www/legacy — восстанавливает из неё.
 */
class KoganContentSeeder extends Seeder
{
    private string $dataDir = __DIR__.'/data/kogan';

    public function run(): void
    {
        $pages = $this->read('pages.json');
        $articles = $this->read('articles.json');
        $books = $this->read('books.json');

        if ($pages === [] && $articles === []) {
            $this->command->warn(
                'Дамп kogan пуст — сначала выполните: php artisan kogan:import'
            );

            return;
        }

        $this->restoreAssets();
        $admin = User::query()->where('is_admin', true)->first();

        foreach ($pages as $record) {
            Page::query()->updateOrCreate(['slug' => $record['slug']], [
                'title' => $record['title'],
                'excerpt' => $record['excerpt'],
                'body' => $record['body'],
                'is_published' => true,
            ]);
        }

        foreach ($articles as $record) {
            Article::query()->updateOrCreate(['slug' => $record['slug']], [
                'user_id' => $admin?->id,
                'title' => $record['title'],
                'excerpt' => $record['excerpt'],
                'body' => $record['body'],
                'image_path' => $record['image_path'],
                'is_published' => true,
                'published_at' => $record['published_at'],
            ]);
        }

        Book::query()->delete();

        foreach ($books as $record) {
            Book::query()->create([
                'title' => $record['title'],
                'author' => $record['author'],
                'description' => $record['description'],
            ]);
        }

        $this->seedMenu();

        $this->command->info(sprintf(
            'Kogan: %d страниц, %d статей, %d книг, %d файлов.',
            count($pages), count($articles), count($books), count($this->read('assets.json'))
        ));
    }

    /** Пункты верхнего меню в духе mainmenu.php старого сайта. */
    private function seedMenu(): void
    {
        MenuItem::query()->delete();

        $tree = [
            ['Новости', '/news', []],
            ['О Когане', '/about', [
                ['Биография', '/about'],
                ['Научная деятельность', '/about/science'],
                ['Труды', '/about/works'],
                ['Характеристики', '/about/character'],
                ['Фото и видео', '/about/photo'],
            ]],
            ['Библиотека', '/biblioteka', [
                ['Книги', '/biblioteka/knigi'],
                ['Статьи', '/biblioteka/stati'],
            ]],
            ['Исследования', '/investigation', [
                ['Письма', '/investigation/letters'],
            ]],
            ['Семинары', '/seminars', []],
            ['Конференции', '/conf', []],
            ['Система БИОЛ', '/biol', []],
            ['Ссылки', '/links', []],
            ['Контакты', '/contacts', []],
        ];

        foreach ($tree as $sort => [$label, $url, $children]) {
            $parent = MenuItem::query()->create([
                'label' => $label,
                'url' => $url,
                'sort_order' => $sort * 10,
            ]);

            foreach ($children as $childSort => [$childLabel, $childUrl]) {
                MenuItem::query()->create([
                    'label' => $childLabel,
                    'url' => $childUrl,
                    'parent_id' => $parent->id,
                    'sort_order' => $childSort * 10,
                ]);
            }
        }
    }

    private function restoreAssets(): void
    {
        $disk = Storage::disk('public');
        $restored = 0;

        foreach ($this->read('assets.json') as $path) {
            $target = "kogan/{$path}";

            if ($disk->exists($target)) {
                continue;
            }

            $source = "/var/www/legacy/{$path}";

            if (is_file($source)) {
                $disk->put($target, file_get_contents($source));
                $restored++;
            }
        }

        if ($restored > 0) {
            $this->command->line("Kogan: восстановлено файлов из legacy — {$restored}.");
        }
    }

    private function read(string $name): array
    {
        $file = "{$this->dataDir}/{$name}";

        if (! is_file($file)) {
            return [];
        }

        return json_decode((string) file_get_contents($file), true) ?: [];
    }
}
