import CartDrawer from '@/Components/storefront/CartDrawer';
import { Button } from '@/Components/ui/button';
import { Toaster } from '@/Components/ui/sonner';
import { User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Menu, ShoppingCart, Store, X } from 'lucide-react';
import { PropsWithChildren, useEffect, useState } from 'react';
import { toast } from 'sonner';

export default function StorefrontLayout({ children }: PropsWithChildren) {
    const { auth, cart, flash } = usePage().props;
    const user = auth.user as User | null;
    const [cartOpen, setCartOpen] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('home')}
                        className="flex items-center gap-2 text-lg font-bold text-foreground"
                    >
                        <Store className="h-6 w-6 text-primary" />
                        Multi-Vendor
                    </Link>

                    <nav className="hidden gap-6 text-sm md:flex">
                        <Link
                            href={route('home')}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            Home
                        </Link>
                        <Link
                            href={route('catalog')}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            Shop
                        </Link>
                    </nav>

                    <div className="ml-auto flex items-center gap-2">
                        <button
                            onClick={() => setMobileOpen((o) => !o)}
                            className="rounded-md p-2 text-foreground hover:bg-accent md:hidden"
                            aria-label="Toggle menu"
                        >
                            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                        {user ? (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={route('dashboard')}>
                                        <LayoutDashboard className="h-4 w-4" />
                                        <span className="hidden sm:inline">Account</span>
                                    </Link>
                                </Button>
                                <button
                                    onClick={() => setCartOpen(true)}
                                    className="relative rounded-md p-2 text-foreground hover:bg-accent"
                                    aria-label="Open cart"
                                >
                                    <ShoppingCart className="h-5 w-5" />
                                    {cart && cart.count > 0 && (
                                        <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs font-semibold text-primary-foreground">
                                            {cart.count}
                                        </span>
                                    )}
                                </button>
                            </>
                        ) : (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={route('login')}>Log in</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href={route('register')}>Sign up</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
                {mobileOpen && (
                    <div className="border-t border-border md:hidden">
                        <nav className="space-y-1 px-4 py-3">
                            <Link
                                href={route('home')}
                                onClick={() => setMobileOpen(false)}
                                className="block rounded-md px-3 py-2 text-sm hover:bg-accent"
                            >
                                Home
                            </Link>
                            <Link
                                href={route('catalog')}
                                onClick={() => setMobileOpen(false)}
                                className="block rounded-md px-3 py-2 text-sm hover:bg-accent"
                            >
                                Shop
                            </Link>
                            {user && (
                                <Link
                                    href={route('orders.index')}
                                    onClick={() => setMobileOpen(false)}
                                    className="block rounded-md px-3 py-2 text-sm hover:bg-accent"
                                >
                                    My Orders
                                </Link>
                            )}
                        </nav>
                    </div>
                )}
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t border-border py-8 text-center text-sm text-muted-foreground">
                Multi-Vendor Marketplace — a portfolio demo.
            </footer>

            <CartDrawer open={cartOpen} onOpenChange={setCartOpen} cart={cart} />
            <Toaster richColors position="top-center" />
        </div>
    );
}
