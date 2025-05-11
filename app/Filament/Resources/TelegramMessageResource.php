<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramMessageResource\Pages;
use App\Filament\Resources\TelegramMessageResource\RelationManagers;
use App\Filament\Resources\TelegramMessageResource\RelationManagers\TelegramMessageMediasRelationManager;
use App\Models\TelegramMessage;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelegramMessageResource extends Resource
{
    protected static ?string $model = TelegramMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message_content')
                ->autosize()
                ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('sent_at')
                    ->required(),
            Section::make()
                ->schema(
                    [
                        SpatieMediaLibraryFileUpload::make('Images')
                            ->multiple()
                            ->previewable()
                            ->collection('products'),
                    ]
                ),
 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            Tables\Columns\SpatieMediaLibraryImageColumn::class::make('media')
                ->collection('products')
                ->conversion('preview')
                ->label(''),
            Tables\Columns\TextColumn::make('telegramChannel.title')
                    ->searchable()
                    ->label('Channel')
                    ->sortable()
                    ->toggleable(), 
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
            Tables\Columns\TextColumn::make('grouped_id')
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('message_content')
                    ->limit()
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->poll('9s')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TelegramMessageMediasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramMessages::route('/'),
            'create' => Pages\CreateTelegramMessage::route('/create'),
            'edit' => Pages\EditTelegramMessage::route('/{record}/edit'),
        ];
    }
}
