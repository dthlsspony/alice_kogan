<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationLabel = 'Страницы';

    protected static ?string $modelLabel = 'страница';

    protected static ?string $pluralModelLabel = 'Страницы';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Редактирование страницы')
                    ->tabs([
                        Tab::make('Контент')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    // Автослаг только если слаг ещё не заполнен вручную.
                                    ->afterStateUpdated(fn ($state, $set, $get, $operation) => $operation === 'create' && ! $get('slug')
                                        ? $set('slug', slugify($state))
                                        : null),

                                TextInput::make('slug')
                                    ->label('Адрес (URL)')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Может быть вложенным: about/author. Страница откроется по адресу /{URL}'),

                                Textarea::make('excerpt')
                                    ->label('Краткое описание')
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                RichEditor::make('body')
                                    ->label('Содержимое')
                                    ->required()
                                    ->columnSpanFull(),

                                Toggle::make('is_published')
                                    ->label('Опубликована')
                                    ->default(true),
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO-заголовок (title)')
                                    ->maxLength(255)
                                    ->hint('Оптимально 50–70 символов. Пусто — используется заголовок страницы')
                                    ->columnSpanFull(),

                                Textarea::make('meta_description')
                                    ->label('SEO-описание (meta description)')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->hint('Оптимально 150–160 символов. Пусто — используется краткое описание')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Адрес')
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => '/'.$state),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Опубл.')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Опубликованные'),
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
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
