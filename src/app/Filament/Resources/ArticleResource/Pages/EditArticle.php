<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Filament v5 при замене файла на уже существующей записи держит в
     * состоянии поля оба значения [старый_путь, новая_загрузка], и dehydration
     * для одиночного поля берёт первое — в БД уходил старый путь. Оставляем
     * в сырном состоянии только самый свежий элемент.
     */
    public function beforeValidate(): void
    {
        $schema = $this->getSchema('form');
        $component = $schema?->getFlatComponents()['image_path'] ?? null;

        if (! $component) {
            return;
        }

        $raw = Arr::wrap($component->getRawState());

        if (count($raw) > 1) {
            $component->rawState([array_last($raw)]);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        $oldPath = $record->image_path;

        $record = parent::handleRecordUpdate($record, $data);

        // При замене баннера удаляем старый файл с диска.
        if (filled($oldPath)
            && ($data['image_path'] ?? null) !== $oldPath
            && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $record;
    }
}
