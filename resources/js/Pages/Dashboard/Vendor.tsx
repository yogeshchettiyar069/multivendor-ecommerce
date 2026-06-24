import SalesChart from '@/Components/SalesChart';
import StatCard from '@/Components/StatCard';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, DollarSign, Package, Wallet } from 'lucide-react';

interface Props {
    vendor: {
        storeName: string;
        status: string;
        commissionRate: number;
    } | null;
    stats: {
        products: number;
        lowStock: number;
        payoutTotalCents: number;
        pendingPayouts: number;
    } | null;
    sales: Array<{ label: string; cents: number }>;
}

export default function VendorDashboard({ vendor, stats, sales }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-foreground">
                        {vendor ? vendor.storeName : 'Vendor Dashboard'}
                    </h2>
                    {vendor && (
                        <Badge variant={vendor.status === 'approved' ? 'success' : 'warning'}>
                            {vendor.status === 'approved' ? 'Approved' : 'Pending approval'}
                        </Badge>
                    )}
                </div>
            }
        >
            <Head title="Vendor Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {vendor && vendor.status !== 'approved' && (
                    <Card className="border-amber-300 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/20">
                        <CardContent className="flex items-start gap-3 py-4">
                            <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-600" />
                            <div className="text-sm text-amber-800 dark:text-amber-200">
                                Your store is awaiting admin approval. You'll be able to publish
                                products once it's approved.
                            </div>
                        </CardContent>
                    </Card>
                )}

                {stats && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard title="Products" value={stats.products} icon={Package} />
                        <StatCard
                            title="Low Stock"
                            value={stats.lowStock}
                            icon={AlertTriangle}
                            hint="Variants under 5 units"
                        />
                        <StatCard
                            title="Payouts Earned"
                            value={formatCents(stats.payoutTotalCents)}
                            icon={DollarSign}
                        />
                        <StatCard
                            title="Pending Payouts"
                            value={stats.pendingPayouts}
                            icon={Wallet}
                        />
                    </div>
                )}

                {sales.length > 0 && <SalesChart data={sales} />}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Your catalogue</CardTitle>
                        {vendor?.status === 'approved' && (
                            <Button asChild size="sm">
                                <Link href={route('vendor.products.index')}>
                                    <Package className="h-4 w-4" /> Manage products
                                </Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        Add and manage your products and variants from the Products area. Your
                        commission rate is{' '}
                        <span className="font-medium text-foreground">
                            {vendor ? `${Math.round(vendor.commissionRate * 100)}%` : '—'}
                        </span>
                        .
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
