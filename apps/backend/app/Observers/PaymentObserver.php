<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\OrderService;

class PaymentObserver
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Quand un paiement passe en "validé", vérifier si la commande est entièrement réglée.
     */
    public function updated(Payment $payment): void
    {
        if (
            $payment->wasChanged('status')
            && $payment->status === PaymentStatus::Validated
            && $payment->order_id !== null
        ) {
            $this->orderService->checkIfFullyPaid($payment->order);
        }
    }

    /**
     * Quand un paiement validé est supprimé et que la commande était payée,
     * repasser la commande en attente.
     */
    public function deleted(Payment $payment): void
    {
        if ($payment->order_id === null) {
            return;
        }

        $order = $payment->order;

        if ($order && $order->status === OrderStatus::Paid && $order->remaining_amount > 0) {
            $order->update(['status' => OrderStatus::Pending, 'paid_at' => null]);
        }
    }
}
