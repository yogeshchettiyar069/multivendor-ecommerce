<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the demo dataset: admin, vendors, category tree, products with
     * variants, and customers with order history.
     *
     * Idempotent: the Docker entrypoint runs this on every boot, so we bail out
     * once the dataset already exists (keyed off the known admin account).
     */
    public function run(): void
    {
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->command->info('Demo data already present — skipping seed.');

            return;
        }

        $this->call([
            CategorySeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
