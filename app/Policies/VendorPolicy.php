<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->owns($user, $vendor);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->owns($user, $vendor);
    }

    /**
     * Approving/suspending vendors is an admin-only action, handled by the
     * before() bypass; non-admins are always denied here.
     */
    public function approve(User $user, Vendor $vendor): bool
    {
        return false;
    }

    private function owns(User $user, Vendor $vendor): bool
    {
        return (string) $user->_id === (string) $vendor->user_id;
    }
}
