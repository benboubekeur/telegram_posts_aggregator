<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramChannelResource\Pages;
use App\Filament\Resources\TelegramChannelResource\RelationManagers;
use App\Models\TelegramChannel;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelegramChannelResource extends Resource
{
    protected static ?string $model = TelegramChannel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telegram Channel')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->live()
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Forms\Components\TextInput::make('channel_identifier')
                            ->live()
                            ->unique(ignoreRecord: true)
                            ->required(),
                    ]),

                   
         

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel_identifier')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramChannels::route('/'),
            'create' => Pages\CreateTelegramChannel::route('/create'),
            'edit' => Pages\EditTelegramChannel::route('/{record}/edit'),
        ];
    }
}
