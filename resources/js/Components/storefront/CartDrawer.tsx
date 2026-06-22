import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { formatCents } from '@/lib/format';
import { CartSummary } from '@/types';
import { router } from '@inertiajs/react';
import { ImageOff, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    cart: CartSummary | null;
}

export default function CartDrawer({ open, onOpenChange, cart }: Props) {
    const items = cart?.items ?? [];

    const setQuantity = (itemId: string, quantity: number) => {
        if (quantity < 1) return;
        router.patch(
            route('cart.update', itemId),
            { quantity },
            { preserveScroll: true, preserveState: true },
        );
    };

    const remove = (itemId: string) =>
        router.delete(route('cart.destroy', itemId), { preserveScroll: true, preserveState: true });

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Your Cart</SheetTitle>
                </SheetHeader>

                {items.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
                        <ShoppingBag className="h-10 w-10 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">Your cart is empty.</p>
                    </div>
                ) : (
                    <>
                        <div className="flex-1 overflow-y-auto px-6">
                            {items.map((item) => (
                                <div
                                    key={item.item_id}
                                    className="flex gap-3 border-b border-border py-4"
                                >
                                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-muted">
                                        {item.thumbnail_url ? (
                                            <img
                                                src={item.thumbnail_url}
                                                alt=""
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-full w-full items-center justify-center">
                                                <ImageOff className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-foreground">
                                            {item.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.variant_label}
                                        </p>
                                        <div className="mt-2 flex items-center gap-2">
                                            <div className="flex items-center rounded-md border border-border">
                                                <button
                                                    type="button"
                                                    className="px-2 py-1 disabled:opacity-40"
                                                    disabled={item.quantity <= 1}
                                                    onClick={() =>
                                                        setQuantity(item.item_id, item.quantity - 1)
                                                    }
                                                    aria-label="Decrease quantity"
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </button>
                                                <span className="w-8 text-center text-sm">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="px-2 py-1 disabled:opacity-40"
                                                    disabled={item.quantity >= item.stock}
                                                    onClick={() =>
                                                        setQuantity(item.item_id, item.quantity + 1)
                                                    }
                                                    aria-label="Increase quantity"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </button>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => remove(item.item_id)}
                                                className="text-muted-foreground hover:text-destructive"
                                                aria-label="Remove item"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div className="text-right text-sm font-medium text-foreground">
                                        {formatCents(item.line_total_cents)}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="space-y-4 border-t border-border p-6">
                            <div className="flex items-center justify-between font-medium text-foreground">
                                <span>Subtotal</span>
                                <span>{formatCents(cart?.subtotalCents ?? 0)}</span>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Shipping and taxes are calculated at checkout.
                            </p>
                            {/* Checkout is wired up in the next phase. */}
                            <Button className="w-full" disabled>
                                Proceed to Checkout
                            </Button>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
