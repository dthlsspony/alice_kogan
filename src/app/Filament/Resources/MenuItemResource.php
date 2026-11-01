<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationLabel = 'Меню сайта';

    protected static ?string $modelLabel = 'пункт меню';

    protected static ?string $pluralModelLabel = 'Пункты меню';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Название')
                    ->required()
                    ->maxLength(100),

                TextInput::make('url')
                    ->label('Ссылка')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Путь на сайте (например /articles) или полный URL (https://...)'),

                Select::make('parent_id')
                    ->label('Родительский пункт')
                    ->relationship('parent', 'label')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->helperText('Оставьте пустым для пункта верхнего уровня. Вложенность — до 2 уровней.'),

                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Select::make('target')
                    ->label('Открывать')
                    ->options([
                        '_self' => 'В том же окне',
                        '_blank' => 'В новой вкладке',
                    ])
                    ->default('_self'),

                Toggle::make('is_visible')
                    ->label('Показывать на сайте')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Название')
                    ->weight('medium')
                    ->formatStateUsing(fn (MenuItem $record): string => $record->parent_id ? '— '.$record->label : $record->label)
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Ссылка')
                    ->limit(40),

                Tables\Columns\TextColumn::make('parent.label')
                    ->label('Родитель'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Видим')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
