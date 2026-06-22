<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Admins may do anything; other roles fall through to the checks below.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->isVendor() && (bool) $user->vendor?->isApproved();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->owns($user, $product) && (bool) $user->vendor?->isApproved();
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    /**
     * A vendor owns a product only when it belongs to their store.
     */
    private function owns(User $user, Product $product): bool
    {
        return $user->isVendor()
            && $user->vendor !== null
            && (string) $user->vendor->_id === (string) $product->vendor_id;
    }
}
