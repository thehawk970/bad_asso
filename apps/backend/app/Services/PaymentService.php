<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;

class PaymentService
{
    /**
     * Valide un paiement en attente et lui attribue une méthode.
     * L'OrderObserver prend le relais via PaymentObserver → OrderService.
     */
    public function validate(Payment $payment, PaymentMethod $method): void
    {
        $payment->update([
            'status' => PaymentStatus::Validated,
            'method' => $method->value,
        ]);
    }
}
