import ImageDropzone from '@/Components/ImageDropzone';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface VariantRow {
    id: string | null;
    sku: string;
    size: string;
    color: string;
    price: string;
    stock: string;
}

export interface ProductFormData {
    name: string;
    category_id: string;
    description: string;
    base_price: string;
    status: string;
    thumbnail: File | null;
    variants: VariantRow[];
}

interface Props {
    mode: 'create' | 'edit';
    action: string;
    initial: ProductFormData;
    categories: Array<{ id: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
    existingThumbnailUrl?: string | null;
}

const emptyVariant: VariantRow = { id: null, sku: '', size: '', color: '', price: '', stock: '' };

export default function ProductForm({
    mode,
    action,
    initial,
    categories,
    statuses,
    existingThumbnailUrl,
}: Props) {
    const form = useForm<ProductFormData>(initial);
    const { data, setData, errors, processing } = form;

    // Variant errors arrive under dot-keyed paths (e.g. "variants.0.sku").
    const variantError = (index: number, field: string): string | undefined =>
        (errors as Record<string, string | undefined>)[`variants.${index}.${field}`];

    const updateVariant = (index: number, field: keyof VariantRow, value: string) => {
        setData(
            'variants',
            data.variants.map((row, i) => (i === index ? { ...row, [field]: value } : row)),
        );
    };

    const addVariant = () => setData('variants', [...data.variants, { ...emptyVariant }]);
    const removeVariant = (index: number) =>
        setData(
            'variants',
            data.variants.filter((_, i) => i !== index),
        );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (mode === 'edit') {
            // Multipart can't use PUT directly, so spoof the method over POST.
            form.transform((d) => ({ ...d, _method: 'put' }));
        }
        form.post(action, { forceFormData: true });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Product details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="mt-1"
                            />
                            {errors.name && (
                                <p className="mt-1 text-sm text-destructive">{errors.name}</p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label>Category</Label>
                                <Select
                                    value={data.category_id}
                                    onValueChange={(v) => setData('category_id', v)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((c) => (
                                            <SelectItem key={c.id} value={c.id}>
                                                {c.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.category_id && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.category_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(v) => setData('status', v)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.status && (
                                    <p className="mt-1 text-sm text-destructive">{errors.status}</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="base_price">Base price (USD)</Label>
                            <Input
                                id="base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.base_price}
                                onChange={(e) => setData('base_price', e.target.value)}
                                className="mt-1"
                            />
                            {errors.base_price && (
                                <p className="mt-1 text-sm text-destructive">{errors.base_price}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                rows={5}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="mt-1 flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-destructive">{errors.description}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Thumbnail</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ImageDropzone
                            value={data.thumbnail}
                            existingUrl={existingThumbnailUrl}
                            onChange={(file) => setData('thumbnail', file)}
                            error={errors.thumbnail as string | undefined}
                        />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Variants</CardTitle>
                    <Button type="button" variant="outline" size="sm" onClick={addVariant}>
                        <Plus className="h-4 w-4" /> Add variant
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="hidden gap-3 px-1 text-xs font-medium text-muted-foreground sm:grid sm:grid-cols-[1.5fr_1fr_1fr_1fr_1fr_auto]">
                        <span>SKU</span>
                        <span>Size</span>
                        <span>Color</span>
                        <span>Price (USD)</span>
                        <span>Stock</span>
                        <span />
                    </div>

                    {data.variants.map((variant, index) => (
                        <div
                            key={index}
                            className="grid gap-3 sm:grid-cols-[1.5fr_1fr_1fr_1fr_1fr_auto]"
                        >
                            <div>
                                <Input
                                    placeholder="SKU"
                                    value={variant.sku}
                                    onChange={(e) => updateVariant(index, 'sku', e.target.value)}
                                />
                                {variantError(index, 'sku') && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {variantError(index, 'sku')}
                                    </p>
                                )}
                            </div>
                            <Input
                                placeholder="Size"
                                value={variant.size}
                                onChange={(e) => updateVariant(index, 'size', e.target.value)}
                            />
                            <Input
                                placeholder="Color"
                                value={variant.color}
                                onChange={(e) => updateVariant(index, 'color', e.target.value)}
                            />
                            <Input
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                value={variant.price}
                                onChange={(e) => updateVariant(index, 'price', e.target.value)}
                            />
                            <Input
                                type="number"
                                min="0"
                                placeholder="0"
                                value={variant.stock}
                                onChange={(e) => updateVariant(index, 'stock', e.target.value)}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeVariant(index)}
                                disabled={data.variants.length === 1}
                                aria-label="Remove variant"
                            >
                                <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    ))}
                    {errors.variants && (
                        <p className="text-sm text-destructive">{errors.variants as string}</p>
                    )}
                </CardContent>
            </Card>

            <div className="flex justify-end gap-3">
                <Button type="submit" disabled={processing}>
                    {mode === 'create' ? 'Create product' : 'Save changes'}
                </Button>
            </div>
        </form>
    );
}
