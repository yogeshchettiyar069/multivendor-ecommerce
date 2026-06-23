<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = Vendor::with('user')->orderBy('created_at', 'desc');
        if (in_array($status, VendorStatus::values(), true)) {
            $query->where('status', $status);
        }

        $vendors = $query->paginate(12)->withQueryString();

        return Inertia::render('Admin/Vendors/Index', [
            'vendors' => [
                'data' => collect($vendors->items())->map(fn (Vendor $v): array => [
                    'id' => (string) $v->_id,
                    'store_name' => $v->store_name,
                    'slug' => $v->slug,
                    'status' => $v->status->value,
                    'commission_rate' => $v->commission_rate,
                    'owner' => $v->user?->name,
                    'email' => $v->user?->email,
                    'products' => Product::where('vendor_id', (string) $v->_id)->count(),
                ])->all(),
                'links' => $vendors->linkCollection()->all(),
                'meta' => [
                    'total' => $vendors->total(),
                    'from' => $vendors->firstItem(),
                    'to' => $vendors->lastItem(),
                ],
            ],
            'filters' => ['status' => $status],
            'pendingCount' => Vendor::where('status', VendorStatus::Pending->value)->count(),
        ]);
    }

    public function approve(Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        $vendor->update(['status' => VendorStatus::Approved]);

        return back()->with('success', "{$vendor->store_name} approved.");
    }

    public function suspend(Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        $vendor->update(['status' => VendorStatus::Suspended]);

        return back()->with('success', "{$vendor->store_name} suspended.");
    }
}
