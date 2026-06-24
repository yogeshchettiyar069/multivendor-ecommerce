import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import { CheckCircle2, ClipboardList, MapPin, Package, Truck } from 'lucide-react';

const STEPS = [
    { key: 'placed', label: 'Order Placed', icon: ClipboardList },
    { key: 'packed', label: 'Packed', icon: Package },
    { key: 'shipped', label: 'Shipped', icon: Truck },
    { key: 'out_for_delivery', label: 'Out for Delivery', icon: MapPin },
    { key: 'delivered', label: 'Delivered', icon: CheckCircle2 },
];

export default function OrderTracker({ status }: { status: string }) {
    const current = Math.max(
        0,
        STEPS.findIndex((s) => s.key === status),
    );
    const pct = (current / (STEPS.length - 1)) * 100;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Track order</CardTitle>
            </CardHeader>
            <CardContent>
                {/* Horizontal (tablet/desktop) */}
                <div className="relative hidden px-2 pt-2 sm:block">
                    <div className="absolute left-7 right-7 top-7 h-1 rounded-full bg-muted" />
                    <div
                        className="absolute left-7 top-7 h-1 rounded-full bg-primary transition-all duration-700 ease-out"
                        style={{ width: `calc((100% - 3.5rem) * ${pct / 100})` }}
                    />
                    <div className="relative flex justify-between">
                        {STEPS.map((s, i) => {
                            const done = i < current;
                            const active = i === current;
                            const Icon = s.icon;
                            return (
                                <div
                                    key={s.key}
                                    className="flex w-24 flex-col items-center gap-2 text-center"
                                >
                                    <div
                                        className={cn(
                                            'flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-500',
                                            done || active
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-background text-muted-foreground',
                                            active && 'ring-4 ring-primary/20',
                                        )}
                                    >
                                        {done ? (
                                            <CheckCircle2 className="h-5 w-5" />
                                        ) : (
                                            <Icon
                                                className={cn('h-5 w-5', active && 'animate-pulse')}
                                            />
                                        )}
                                    </div>
                                    <span
                                        className={cn(
                                            'text-xs leading-tight',
                                            done || active
                                                ? 'font-medium text-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {s.label}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Vertical (mobile) */}
                <div className="sm:hidden">
                    {STEPS.map((s, i) => {
                        const done = i < current;
                        const active = i === current;
                        const Icon = s.icon;
                        return (
                            <div key={s.key} className="flex gap-3">
                                <div className="flex flex-col items-center">
                                    <div
                                        className={cn(
                                            'flex h-9 w-9 items-center justify-center rounded-full border-2 transition-all duration-500',
                                            done || active
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border text-muted-foreground',
                                            active && 'ring-4 ring-primary/20',
                                        )}
                                    >
                                        {done ? (
                                            <CheckCircle2 className="h-4 w-4" />
                                        ) : (
                                            <Icon
                                                className={cn('h-4 w-4', active && 'animate-pulse')}
                                            />
                                        )}
                                    </div>
                                    {i < STEPS.length - 1 && (
                                        <div
                                            className={cn(
                                                'my-1 min-h-8 w-0.5 flex-1 transition-colors duration-500',
                                                i < current ? 'bg-primary' : 'bg-muted',
                                            )}
                                        />
                                    )}
                                </div>
                                <div className="pb-6 pt-1.5">
                                    <p
                                        className={cn(
                                            'text-sm',
                                            done || active
                                                ? 'font-medium text-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {s.label}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
