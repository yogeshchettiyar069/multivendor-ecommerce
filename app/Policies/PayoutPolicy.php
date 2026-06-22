<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, Payout $payout): bool
    {
        return $user->isVendor()
            && $user->vendor !== null
            && (string) $user->vendor->_id === (string) $payout->vendor_id;
    }
}
