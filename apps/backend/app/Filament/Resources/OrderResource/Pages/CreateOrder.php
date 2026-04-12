<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        /** @var Order $order */
        $order = $this->record;

        $order->recalculateTotal();

        // Créer une licence en attente si la commande contient un produit licence
        $order->autoCreatePendingLicense();

        // Créer un paiement en attente par article
        $order->items()->with('product')->each(function (OrderItem $item) use ($order): void {
            $order->payments()->create([
                'player_id' => $order->player_id,
                'amount'    => $item->unit_price * $item->quantity,
                'status'    => PaymentStatus::Pending,
                'reference' => $item->product?->name,
            ]);
        });
    }
}
