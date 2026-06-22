<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = $this->makeCustomers();

        /** @var Collection<int, Product> $products */
        $products = Product::all()->filter(fn (Product $p): bool => $p->variants->isNotEmpty())->values();

        if ($products->isEmpty()) {
            return;
        }

        // vendor_id => commission_rate
        $commissionByVendor = Vendor::all()->mapWithKeys(
            fn (Vendor $v): array => [(string) $v->_id => (float) $v->commission_rate]
        );

        foreach ($customers as $customer) {
            foreach (range(1, fake()->numberBetween(1, 3)) as $ignored) {
                $this->makeOrder($customer, $products, $commissionByVendor);
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function makeCustomers(): Collection
    {
        $known = User::create([
            'name' => 'Casey Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Customer,
            'email_verified_at' => now(),
        ]);

        return User::factory()->customer()->count(4)->create()->prepend($known);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<string, float>  $commissionByVendor
     */
    private function makeOrder(User $customer, $products, $commissionByVendor): void
    {
        $lineItems = [];
        $subtotal = 0;

        foreach ($products->random(fake()->numberBetween(1, 4)) as $product) {
            /** @var Product $product */
            $variant = $product->variants->random();
            $quantity = fake()->numberBetween(1, 3);
            $unitPrice = (int) $variant->price_cents;

            $lineItems[] = [
                'product_id' => (string) $product->_id,
                'variant_id' => (string) $variant->_id,
                'vendor_id' => (string) $product->vendor_id,
                'unit_price_cents' => $unitPrice,
                'quantity' => $quantity,
            ];

            $subtotal += $unitPrice * $quantity;
        }

        $status = fake()->randomElement([
            OrderStatus::Paid,
            OrderStatus::Paid,
            OrderStatus::Fulfilled,
            OrderStatus::Cancelled,
        ]);

        $order = Order::create([
            'user_id' => (string) $customer->_id,
            'status' => $status,
            'subtotal_cents' => $subtotal,
            'total_cents' => $subtotal,
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('############################'),
            'placed_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);

        foreach ($lineItems as $item) {
            $order->items()->create($item);
        }

        $this->makePayouts($order, $lineItems, $commissionByVendor, $status);
    }

    /**
     * Split the order per vendor and create a payout net of commission.
     *
     * @param  array<int, array{vendor_id: string, unit_price_cents: int, quantity: int}>  $lineItems
     * @param  Collection<string, float>  $commissionByVendor
     */
    private function makePayouts(Order $order, array $lineItems, $commissionByVendor, OrderStatus $status): void
    {
        if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return;
        }

        $grossByVendor = [];
        foreach ($lineItems as $item) {
            $grossByVendor[$item['vendor_id']] = ($grossByVendor[$item['vendor_id']] ?? 0)
                + $item['unit_price_cents'] * $item['quantity'];
        }

        foreach ($grossByVendor as $vendorId => $gross) {
            $rate = $commissionByVendor->get($vendorId, 0.10);
            $amount = (int) round($gross * (1 - $rate));

            Payout::create([
                'vendor_id' => $vendorId,
                'order_id' => (string) $order->_id,
                'amount_cents' => $amount,
                'status' => $status === OrderStatus::Fulfilled ? PayoutStatus::Paid : PayoutStatus::Pending,
            ]);
        }
    }
}
