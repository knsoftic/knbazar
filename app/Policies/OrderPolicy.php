<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Customer-side order authorisation. Admin access is controlled by the
 * manage_orders / manage_payments gates.
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id !== null && $order->user_id === $user->getKey();
    }

    public function pay(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canSubmitPayment();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canBeCancelledByCustomer();
    }
}
