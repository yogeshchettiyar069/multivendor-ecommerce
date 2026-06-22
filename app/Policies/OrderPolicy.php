<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isCustomer() || $user->isVendor();
    }

    /**
     * The customer who placed the order may view it; a vendor may view it only
     * if it contains at least one of their line items.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isCustomer()) {
            return (string) $order->user_id === (string) $user->_id;
        }

        if ($user->isVendor() && $user->vendor !== null) {
            return $order->items->contains(
                fn ($item): bool => (string) $item->vendor_id === (string) $user->vendor->_id
            );
        }

        return false;
    }
}
