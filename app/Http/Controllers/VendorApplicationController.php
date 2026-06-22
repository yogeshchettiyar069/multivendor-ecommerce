<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VendorApplicationController extends Controller
{
    /**
     * Show the "become a vendor" application form.
     */
    public function create(Request $request): RedirectResponse|Response
    {
        if ($request->user()?->vendor !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Vendor/Apply');
    }

    /**
     * Create a pending vendor profile and promote the user to the vendor role.
     * Selling stays blocked until an admin approves the application.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->vendor !== null) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'min:3', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        Vendor::create([
            'user_id' => $user->_id,
            'store_name' => $validated['store_name'],
            'slug' => $this->uniqueSlug($validated['store_name']),
            'status' => VendorStatus::Pending,
            'bio' => $validated['bio'] ?? null,
            'logo_path' => null,
            'commission_rate' => 0.10,
        ]);

        $user->update(['role' => Role::Vendor]);

        return redirect()->route('dashboard')
            ->with('success', 'Your vendor application has been submitted and is pending approval.');
    }

    private function uniqueSlug(string $storeName): string
    {
        $base = Str::slug($storeName);
        $slug = $base;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }
}
