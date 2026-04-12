<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Appelé après la création d'une commande :
     * - recalcule le total
     * - crée un paiement en attente par article
     * - crée une licence en attente si applicable
     */
    public function handleAfterCreate(Order $order, LicenseService $licenseService): void
    {
        $this->recalculateTotal($order);

        $order->items()->with('product')->each(function (OrderItem $item) use ($order): void {
            $order->payments()->create([
                'player_id' => $order->player_id,
                'amount'    => $item->unit_price * $item->quantity,
                'status'    => PaymentStatus::Pending,
                'reference' => $item->product?->name,
            ]);
        });

        $licenseService->ensurePendingLicenseForOrder($order);
    }

    /**
     * Recalcule et persiste le total depuis les lignes de commande.
     */
    public function recalculateTotal(Order $order): void
    {
        $total = $order->items()->sum(DB::raw('unit_price * quantity'));
        $order->update(['total' => $total]);
    }

    /**
     * Marque manuellement la commande comme payée et valide
     * tous les paiements en attente avec la méthode fournie.
     * L'OrderObserver se charge ensuite de la licence.
     */
    public function markAsPaid(Order $order, PaymentMethod $method): void
    {
        $order->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->update([
                'status' => PaymentStatus::Validated->value,
                'method' => $method->value,
            ]);

        $order->update([
            'status'  => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Vérifie si les paiements validés couvrent le total.
     * Si oui, marque la commande comme payée.
     * L'OrderObserver se charge ensuite de la licence.
     *
     * @return bool  true si la commande vient d'être marquée payée
     */
    public function checkIfFullyPaid(Order $order): bool
    {
        if ($order->status === OrderStatus::Paid) {
            return false;
        }

        $totalPaid = $order->payments()
            ->where('status', PaymentStatus::Validated->value)
            ->sum('amount');

        if ($totalPaid < (float) $order->total) {
            return false;
        }

        $order->update([
            'status'  => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        return true;
    }
}
