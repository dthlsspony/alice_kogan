<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationLabel = 'Статьи';

    protected static ?string $modelLabel = 'статья';

    protected static ?string $pluralModelLabel = 'Статьи';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Публикации';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Редактирование статьи')
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
                                    ->label('Слаг (URL)')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Textarea::make('excerpt')
                                    ->label('Краткое описание')
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                FileUpload::make('image_path')
                                    ->label('Иллюстрация (баннер)')
                                    ->disk('public')
                                    ->directory('articles/banners')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(4096)
                                    ->helperText('Широкое изображение для шапки статьи; без него показывается сгенерированный баннер')
                                    ->columnSpanFull(),

                                RichEditor::make('body')
                                    ->label('Текст статьи')
                                    ->required()
                                    ->columnSpanFull(),

                                Toggle::make('is_published')
                                    ->label('Опубликовано')
                                    ->default(true),

                                DateTimePicker::make('published_at')
                                    ->label('Дата публикации')
                                    ->default(now()),

                                Select::make('user_id')
                                    ->label('Автор')
                                    ->relationship('author', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->id()),
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO-заголовок (title)')
                                    ->maxLength(255)
                                    ->hint('Оптимально 50–70 символов. Пусто — используется заголовок статьи')
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
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Илл.')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/placeholder-banner.svg')),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Автор'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Опубл.')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Дата публикации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
