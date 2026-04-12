<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Payment;

class ValidatePayment
{
    public function execute(Payment $payment): Payment
    {
        $payment->update(['status' => PaymentStatus::Validated]);
        $payment->refresh();

        return $payment;
    }
}
