<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationLabel = 'Комментарии';

    protected static ?string $modelLabel = 'комментарий';

    protected static ?string $pluralModelLabel = 'Комментарии';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Публикации';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Comment::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Статус')
                    ->options([
                        Comment::STATUS_PENDING => 'На проверке',
                        Comment::STATUS_APPROVED => 'Одобрен',
                        Comment::STATUS_REJECTED => 'Отклонён',
                    ])
                    ->required()
                    ->default(Comment::STATUS_PENDING),

                Textarea::make('body')
                    ->label('Текст')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Автор')
                    ->default('Гость'),

                Tables\Columns\TextColumn::make('commentable_type')
                    ->label('Раздел')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'quote' => 'Цитата',
                        'book' => 'Книга',
                        'article' => 'Статья',
                        'page' => 'Страница',
                        default => $state,
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('body')
                    ->label('Комментарий')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Comment::STATUS_PENDING => 'На проверке',
                        Comment::STATUS_APPROVED => 'Одобрен',
                        Comment::STATUS_REJECTED => 'Отклонён',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Comment::STATUS_APPROVED => 'success',
                        Comment::STATUS_PENDING => 'warning',
                        Comment::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Comment::STATUS_PENDING => 'На проверке',
                        Comment::STATUS_APPROVED => 'Одобрен',
                        Comment::STATUS_REJECTED => 'Отклонён',
                    ]),
                Tables\Filters\SelectFilter::make('commentable_type')
                    ->label('Раздел')
                    ->options([
                        'quote' => 'Цитаты',
                        'book' => 'Книги',
                        'article' => 'Статьи',
                        'page' => 'Страницы',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_APPROVED)
                    ->action(function (Comment $record): void {
                        $record->update([
                            'status' => Comment::STATUS_APPROVED,
                            'approved_at' => now(),
                        ]);
                    }),

                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_REJECTED)
                    ->action(fn (Comment $record) => $record->update(['status' => Comment::STATUS_REJECTED])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Одобрить выбранные')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['status' => Comment::STATUS_APPROVED, 'approved_at' => now()])),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
