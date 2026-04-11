<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use App\Models\License;
use App\Models\Payment;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPlayer extends ViewRecord
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations')
                ->columns(2)
                ->schema([
                    TextEntry::make('first_name')->label('Prénom'),
                    TextEntry::make('last_name')->label('Nom'),
                    TextEntry::make('email')->label('Email')->default('—'),
                    TextEntry::make('phone')->label('Téléphone')->default('—'),
                    TextEntry::make('created_at')->label('Inscrit le')->dateTime('d/m/Y'),
                ]),

            Section::make('Licences')
                ->schema([
                    RepeatableEntry::make('licenses')
                        ->label('')
                        ->schema([
                            TextEntry::make('season')->label('Saison'),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->getStateUsing(fn (License $record) => $record->status->label())
                                ->color(fn (License $record) => $record->status->color()),
                            TextEntry::make('created_at')->label('Créée le')->dateTime('d/m/Y'),
                        ])
                        ->columns(3),
                ]),

            Section::make('Paiements')
                ->schema([
                    RepeatableEntry::make('payments')
                        ->label('')
                        ->schema([
                            TextEntry::make('amount')->label('Montant')->money('EUR'),
                            TextEntry::make('method')
                                ->label('Méthode')
                                ->getStateUsing(fn (Payment $record) => $record->method->label()),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->getStateUsing(fn (Payment $record) => $record->status->label())
                                ->color(fn (Payment $record) => $record->status->color()),
                            TextEntry::make('reference')->label('Référence')->default('—'),
                            TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y'),
                        ])
                        ->columns(5),
                ]),
        ]);
    }
}
