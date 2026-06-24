<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\VendorStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the dashboard appropriate to the authenticated user's role.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => $this->adminDashboard(),
            $user->isVendor() => $this->vendorDashboard($user),
            default => $this->customerDashboard($user),
        };
    }

    private function adminDashboard(): Response
    {
        $paidStatuses = [OrderStatus::Paid->value, OrderStatus::Fulfilled->value];

        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'pendingVendors' => Vendor::where('status', VendorStatus::Pending->value)->count(),
                'totalVendors' => Vendor::count(),
                'totalProducts' => Product::count(),
                'totalOrders' => Order::count(),
                'revenueCents' => (int) Order::whereIn('status', $paidStatuses)->sum('total_cents'),
            ],
            'recentOrders' => $this->serializeOrders(
                Order::orderBy('placed_at', 'desc')->limit(5)->get()
            ),
        ]);
    }

    private function vendorDashboard(User $user): Response
    {
        $vendor = $user->vendor;

        if (! $vendor instanceof Vendor) {
            return Inertia::render('Dashboard/Vendor', [
                'vendor' => null,
                'stats' => null,
                'sales' => [],
            ]);
        }

        $productCount = Product::where('vendor_id', $vendor->_id)->count();
        $lowStock = Product::where('vendor_id', $vendor->_id)
            ->where('variants.stock', '<', 5)
            ->count();

        return Inertia::render('Dashboard/Vendor', [
            'vendor' => [
                'storeName' => $vendor->store_name,
                'status' => $vendor->status->value,
                'commissionRate' => $vendor->commission_rate,
            ],
            'stats' => [
                'products' => $productCount,
                'lowStock' => $lowStock,
                'payoutTotalCents' => (int) $vendor->payouts()->sum('amount_cents'),
                'pendingPayouts' => $vendor->payouts()->where('status', 'pending')->count(),
            ],
            'sales' => $this->salesSeries((string) $vendor->_id),
        ]);
    }

    /**
     * Monthly sales (this vendor's revenue) for the last 6 months.
     *
     * @return array<int, array{label: string, cents: int}>
     */
    private function salesSeries(string $vendorId): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $buckets = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $buckets[$month->format('Y-m')] = ['label' => $month->format('M'), 'cents' => 0];
        }

        $orders = Order::where('items.vendor_id', $vendorId)
            ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Fulfilled->value])
            ->get(['items', 'placed_at', 'created_at']);

        foreach ($orders as $order) {
            $date = $order->placed_at ?? $order->created_at;
            $key = $date?->format('Y-m');

            if ($key === null || ! isset($buckets[$key])) {
                continue;
            }

            foreach ($order->items as $item) {
                if ((string) $item->vendor_id === $vendorId) {
                    $buckets[$key]['cents'] += (int) $item->unit_price_cents * (int) $item->quantity;
                }
            }
        }

        return array_values($buckets);
    }

    private function customerDashboard(User $user): Response
    {
        $paidStatuses = [OrderStatus::Paid->value, OrderStatus::Fulfilled->value];

        return Inertia::render('Dashboard/Customer', [
            'stats' => [
                'orders' => Order::where('user_id', $user->_id)->count(),
                'totalSpentCents' => (int) Order::where('user_id', $user->_id)
                    ->whereIn('status', $paidStatuses)
                    ->sum('total_cents'),
            ],
            'recentOrders' => $this->serializeOrders(
                Order::where('user_id', $user->_id)->orderBy('placed_at', 'desc')->limit(5)->get()
            ),
        ]);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function serializeOrders($orders): array
    {
        return $orders->map(fn (Order $order): array => [
            'id' => (string) $order->_id,
            'status' => $order->status->value,
            'totalCents' => $order->total_cents,
            'placedAt' => $order->placed_at?->toIso8601String(),
            'itemCount' => $order->items->count(),
        ])->all();
    }
}
