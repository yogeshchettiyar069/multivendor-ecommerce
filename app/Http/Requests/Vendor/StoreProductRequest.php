<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Product::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'string', Rule::exists('categories', '_id')],
            'description' => ['nullable', 'string', 'max:5000'],
            // Entered in dollars; converted to integer cents in the controller.
            'base_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            // Real MIME-type validation (not just the extension), with a size cap.
            'thumbnail' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'variants' => ['required', 'array', 'min:1', 'max:50'],
            'variants.*.id' => ['nullable', 'string'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'distinct'],
            'variants.*.size' => ['nullable', 'string', 'max:32'],
            'variants.*.color' => ['nullable', 'string', 'max:32'],
            'variants.*.price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
