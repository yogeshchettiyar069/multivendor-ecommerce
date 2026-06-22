<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorSeeder extends Seeder
{
    /**
     * Create the platform admin, five approved vendors, and one pending vendor
     * (so the admin approval queue has something to act on).
     */
    public function run(): void
    {
        User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Admin,
            'email_verified_at' => now(),
        ]);

        /** @var array<int, array{store: string, bio: string, rate: float}> $approved */
        $approved = [
            ['store' => 'Nordic Threads', 'bio' => 'Minimalist Scandinavian apparel and accessories.', 'rate' => 0.10],
            ['store' => 'Volt Electronics', 'bio' => 'Cutting-edge gadgets and audio gear.', 'rate' => 0.12],
            ['store' => 'Hearth & Home', 'bio' => 'Cookware and decor for the modern kitchen.', 'rate' => 0.15],
            ['store' => 'PageTurner Books', 'bio' => 'Curated fiction and non-fiction for every reader.', 'rate' => 0.08],
            ['store' => 'Peak Outdoors', 'bio' => 'Gear for cycling, camping and the trail.', 'rate' => 0.12],
        ];

        foreach ($approved as $i => $data) {
            $this->makeVendor($data['store'], 'vendor'.($i + 1).'@example.com', $data['bio'], $data['rate'], VendorStatus::Approved);
        }

        // A pending vendor awaiting admin approval.
        $this->makeVendor('Aspiring Artisans', 'vendor6@example.com', 'Handmade goods, pending review.', 0.10, VendorStatus::Pending);
    }

    private function makeVendor(string $store, string $email, string $bio, float $rate, VendorStatus $status): void
    {
        $user = User::create([
            'name' => $store.' Owner',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Vendor,
            'email_verified_at' => now(),
        ]);

        Vendor::create([
            'user_id' => $user->_id,
            'store_name' => $store,
            'slug' => Str::slug($store),
            'status' => $status,
            'bio' => $bio,
            'logo_path' => null,
            'commission_rate' => $rate,
        ]);
    }
}
