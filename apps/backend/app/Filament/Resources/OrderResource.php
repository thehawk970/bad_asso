<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?int $navigationSort = 6;

    // ─── Formulaire (création / édition) ────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Joueur & paiement')
                ->columns(2)
                ->schema([
                    Select::make('player_id')
                        ->label('Joueur')
                        ->relationship('player', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($r) => $r->last_name . ' ' . $r->first_name)
                        ->searchable()
                        ->required(),

                    Select::make('payment_method')
                        ->label('Moyen de paiement')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $m) => [$m->value => $m->label()]
                        ))
                        ->nullable(),

                    TextInput::make('reference')
                        ->label('Référence (ex: virement REF-2025-01)')
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Articles')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Produit')
                                ->options(Product::active()->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        $set('unit_price', $product?->price);
                                    }
                                })
                                ->columnSpan(2),

                            TextInput::make('quantity')
                                ->label('Qté')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('unit_price')
                                ->label('Prix unitaire (€)')
                                ->numeric()
                                ->required()
                                ->suffix('€')
                                ->columnSpan(1),
                        ])
                        ->columns(4)
                        ->addActionLabel('Ajouter un article')
                        ->minItems(1)
                        ->reorderable(false),
                ]),
        ]);
    }

    // ─── Infolist (vue détail) ───────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commande')
                ->columns(2)
                ->schema([
                    TextEntry::make('player.full_name')
                        ->label('Joueur')
                        ->getStateUsing(fn (Order $record) => $record->player?->full_name ?? '(joueur supprimé)'),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state->color()),
                    TextEntry::make('total')
                        ->label('Total')
                        ->money('EUR'),
                    TextEntry::make('payment_method')
                        ->label('Moyen de paiement')
                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                    TextEntry::make('reference')
                        ->label('Référence')
                        ->default('—'),
                    TextEntry::make('paid_at')
                        ->label('Payé le')
                        ->dateTime('d/m/Y H:i')
                        ->default('—'),
                ]),

            Section::make('Articles')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('product.name')->label('Produit'),
                            TextEntry::make('quantity')->label('Qté'),
                            TextEntry::make('unit_price')->label('Prix unit.')->money('EUR'),
                            TextEntry::make('subtotal')
                                ->label('Sous-total')
                                ->money('EUR')
                                ->getStateUsing(fn ($record) => $record->subtotal),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['player', 'items.product']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.last_name')
                    ->label('Joueur')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, Order $record) => $record->player
                        ? $record->player->last_name . ' ' . $record->player->first_name
                        : '(joueur supprimé)'
                    ),

                TextColumn::make('items_summary')
                    ->label('Articles')
                    ->getStateUsing(function (Order $record): string {
                        return $record->items->map(function ($item) {
                            $name = $item->product?->name ?? '(produit supprimé)';
                            return "{$name} ×{$item->quantity}";
                        })->join(', ');
                    })
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Moyen')
                    ->badge()
                    ->getStateUsing(fn (Order $record) => $record->payment_method?->label() ?? '—'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn (Order $record) => $record->status->label())
                    ->color(fn (Order $record) => $record->status->color()),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('payment_method')
                    ->label('Moyen de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m) => [$m->value => $m->label()]
                    )),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('mark_paid')
                    ->label('Marquer payé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Order $record) => $record->status !== OrderStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer le paiement')
                    ->modalDescription(fn (Order $record) => "Valider le paiement de {$record->player?->full_name} — {$record->total} €")
                    ->form([
                        Select::make('payment_method')
                            ->label('Moyen de paiement')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(
                                fn (PaymentMethod $m) => [$m->value => $m->label()]
                            ))
                            ->required(),

                        TextInput::make('reference')
                            ->label('Référence (facultatif)')
                            ->nullable(),
                    ])
                    ->fillForm(fn (Order $record) => [
                        'payment_method' => $record->payment_method?->value,
                        'reference'      => $record->reference,
                    ])
                    ->action(function (Order $record, array $data): void {
                        $licenseValidated = $record->markAsPaid(
                            PaymentMethod::from($data['payment_method']),
                            $data['reference'] ?? null,
                        );

                        $body = $licenseValidated
                            ? 'La licence du joueur a été validée automatiquement.'
                            : null;

                        Notification::make()
                            ->title("Paiement de {$record->player?->full_name} validé ({$record->total} €)")
                            ->body($body)
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Order $record) => $record->status === OrderStatus::Paid),
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
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
