<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->record;

        return [
            Action::make('mark_paid')
                ->label('Marquer payé')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->hidden(fn () => $order->status !== OrderStatus::Pending)
                ->modalHeading('Confirmer le paiement')
                ->modalDescription(fn () => "Valider le paiement de {$order->player?->full_name} — {$order->total} €")
                ->form([
                    Select::make('method')
                        ->label('Moyen de paiement')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $m) => [$m->value => $m->label()]
                        ))
                        ->required(),
                ])
                ->action(function (array $data) use ($order): void {
                    app(OrderService::class)->markAsPaid(
                        $order,
                        PaymentMethod::from($data['method']),
                    );

                    Notification::make()
                        ->title("Commande payée — {$order->total} €")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'paid_at']);
                }),

            EditAction::make()
                ->hidden(fn () => $order->status === OrderStatus::Paid),
        ];
    }
}
