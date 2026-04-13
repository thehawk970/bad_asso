<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Player;
use App\Models\Product;
use App\Services\OrderService;
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
use Filament\Schemas\Components\Utilities\Set;
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
                        ->getSearchResultsUsing(fn (string $search) => Player::where('last_name', 'ilike', "%{$search}%")
                            ->orWhere('first_name', 'ilike', "%{$search}%")
                            ->orderBy('last_name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Player $p) => [$p->id => $p->last_name.' '.$p->first_name])
                        )
                        ->getOptionLabelUsing(function (int|string $value): string {
                            $player = Player::where('id', $value)->first();

                            return $player !== null ? $player->full_name : '—';
                        })
                        ->searchable()
                        ->required(),

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
                                ->options(fn () => Product::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Set $set): void {
                                    // Snapshot du prix au moment de la sélection
                                    // Ce prix est stocké sur order_items.unit_price
                                    // et n'évoluera pas si le produit change de tarif
                                    $set('unit_price', $state ? (float) Product::find($state)?->price : null);
                                })
                                ->columnSpan(2),

                            TextInput::make('quantity')
                                ->label('Qté')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('unit_price')
                                ->label('Prix (€)')
                                ->numeric()
                                ->required()
                                ->suffix('€')
                                ->readOnly()  // Le prix est copié depuis le catalogue, non modifiable
                                ->columnSpan(1),
                        ])
                        ->columns(4)
                        ->addActionLabel('+ Ajouter un article')
                        ->minItems(1)
                        ->reorderable(false)
                        ->itemLabel(function (array $state): ?string {
                            if (! $state['product_id']) {
                                return null;
                            }
                            $productId = $state['product_id'] ?? null;
                            $product = $productId !== null ? Product::find((int) $productId) : null;
                            $qty = $state['quantity'] ?? 1;
                            $price = $state['unit_price'] ?? 0;
                            $sub = number_format((float) $price * (int) $qty, 2, ',', ' ');

                            return "{$product?->name} × {$qty} = {$sub} €";
                        }),
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
                        ->getStateUsing(function (Order $record): string {
                            $player = $record->player;

                            return $player !== null ? $player->full_name : '(joueur supprimé)';
                        }),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state->color()),
                    TextEntry::make('total')
                        ->label('Total')
                        ->money('EUR'),
                    TextEntry::make('reference')
                        ->label('Référence')
                        ->default('—'),
                    TextEntry::make('paid_at')
                        ->label('Payé le')
                        ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i') ?? '—'),
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

    public static function getEloquentQuery(): Builder
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
                        ? $record->player->last_name.' '.$record->player->first_name
                        : '(joueur supprimé)'
                    ),

                TextColumn::make('items_summary')
                    ->label('Articles')
                    ->getStateUsing(function (Order $record): string {
                        return $record->items->map(function ($item) {
                            $product = $item->product;
                            $name = $product !== null ? $product->name : '(produit supprimé)';

                            return "{$name} ×{$item->quantity}";
                        })->join(', ');
                    })
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Payé')
                    ->money('EUR')
                    ->getStateUsing(fn (Order $record) => $record->amount_paid)
                    ->color(fn (Order $record) => $record->amount_paid >= (float) $record->total ? 'success' : 'warning'),

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

            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('mark_paid')
                    ->label('Marquer payé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Order $record) => $record->status !== OrderStatus::Pending)
                    ->modalHeading('Confirmer le paiement')
                    ->modalDescription(fn (Order $record) => "Valider le paiement de {$record->player?->full_name} — {$record->total} €")
                    ->schema([
                        Select::make('method')
                            ->label('Moyen de paiement')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(
                                fn (PaymentMethod $m) => [$m->value => $m->label()]
                            ))
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(OrderService::class)->markAsPaid(
                            $record,
                            PaymentMethod::from($data['method']),
                        );

                        Notification::make()
                            ->title("Paiement de {$record->player?->full_name} validé ({$record->total} €)")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Order $record) => $record->status === OrderStatus::Paid),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
