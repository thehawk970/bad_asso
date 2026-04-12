<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\LicenseService;

class OrderObserver
{
    public function __construct(private LicenseService $licenseService) {}

    /**
     * Quand une commande passe en "payée", confirmer le paiement sur la licence
     * et tenter de la valider automatiquement.
     */
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === OrderStatus::Paid) {
            $this->licenseService->confirmPaymentForOrder($order);
        }
    }
}
