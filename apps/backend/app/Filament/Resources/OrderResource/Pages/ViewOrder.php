<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                ->requiresConfirmation()
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
                ->fillForm(fn () => [
                    'payment_method' => $order->payment_method?->value,
                    'reference'      => $order->reference,
                ])
                ->action(function (array $data) use ($order): void {
                    $licenseValidated = $order->markAsPaid(
                        PaymentMethod::from($data['payment_method']),
                        $data['reference'] ?? null,
                    );

                    $body = $licenseValidated
                        ? 'La licence du joueur a été validée automatiquement.'
                        : 'Paiement confirmé sur la licence. Les autres conditions restent à remplir.';

                    Notification::make()
                        ->title("Commande payée — {$order->total} €")
                        ->body($body)
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'paid_at', 'payment_method']);
                }),

            EditAction::make()
                ->hidden(fn () => $order->status === OrderStatus::Paid),
        ];
    }
}
