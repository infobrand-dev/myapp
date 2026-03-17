<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Payments\Models\Payment;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->can('payments.view_all')) {
            return true;
        }

        return $user->can('payments.view_own') && (int) $payment->received_by === (int) $user->id;
    }

    public function void(User $user, Payment $payment): bool
    {
        return $user->can('payments.void') && $this->view($user, $payment);
    }
}
