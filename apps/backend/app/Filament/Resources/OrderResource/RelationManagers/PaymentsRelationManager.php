<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Paiements';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label('Montant (€)')
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->suffix('€')
                ->required()
                ->default(fn () => $this->getOwnerRecord()->remaining_amount ?: null),

            Select::make('method')
                ->label('Méthode')
                ->options(collect(PaymentMethod::cases())->mapWithKeys(
                    fn (PaymentMethod $m) => [$m->value => $m->label()]
                ))
                ->required(),

            Select::make('status')
                ->label('Statut')
                ->options(collect(PaymentStatus::cases())->mapWithKeys(
                    fn (PaymentStatus $s) => [$s->value => $s->label()]
                ))
                ->default(PaymentStatus::Validated->value)
                ->required(),

            TextInput::make('reference')
                ->label('Référence (ex: VIR-001)')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->getStateUsing(fn (Payment $record) => $record->method?->label() ?? '—'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn (Payment $record) => $record->status->label())
                    ->color(fn (Payment $record) => $record->status->color()),

                TextColumn::make('reference')
                    ->label('Référence')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un paiement')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['player_id'] = $this->getOwnerRecord()->player_id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateDescription('Aucun paiement enregistré pour cette commande.')
            ->description(function (): string {

                /** @var Order $order */
                $order = $this->getOwnerRecord();
                $paid  = number_format($order->amount_paid, 2, ',', ' ');
                $total = number_format((float) $order->total, 2, ',', ' ');
                $remaining = number_format($order->remaining_amount, 2, ',', ' ');

                if ($order->remaining_amount <= 0) {
                    return "Total : {$total} € — ✓ Entièrement réglé";
                }

                return "Total : {$total} € — Payé : {$paid} € — Reste : {$remaining} €";
            });
    }
}
