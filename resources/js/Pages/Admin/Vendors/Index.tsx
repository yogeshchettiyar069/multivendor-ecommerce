import { Badge, BadgeProps } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Check, Ban } from 'lucide-react';

interface VendorRow {
    id: string;
    store_name: string;
    slug: string;
    status: string;
    commission_rate: number;
    owner: string | null;
    email: string | null;
    products: number;
}

interface Props {
    vendors: {
        data: VendorRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { total: number; from: number | null; to: number | null };
    };
    filters: { status: string | null };
    pendingCount: number;
}

const STATUS_VARIANT: Record<string, BadgeProps['variant']> = {
    approved: 'success',
    pending: 'warning',
    suspended: 'destructive',
};

const TABS = [
    { value: '', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'suspended', label: 'Suspended' },
];

export default function AdminVendors({ vendors, filters, pendingCount }: Props) {
    const filter = (status: string) =>
        router.get(route('admin.vendors.index'), status ? { status } : {}, {
            preserveState: true,
            replace: true,
        });

    const act = (id: string, action: 'approve' | 'suspend') =>
        router.patch(route(`admin.vendors.${action}`, id), {}, { preserveScroll: true });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <h2 className="text-xl font-semibold text-foreground">Vendors</h2>
                    {pendingCount > 0 && <Badge variant="warning">{pendingCount} pending</Badge>}
                </div>
            }
        >
            <Head title="Vendors" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-4 flex gap-2">
                    {TABS.map((t) => (
                        <button
                            key={t.value}
                            onClick={() => filter(t.value)}
                            className={
                                'rounded-md px-3 py-1.5 text-sm transition-colors ' +
                                ((filters.status ?? '') === t.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'border border-border hover:bg-accent')
                            }
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Store</TableHead>
                                    <TableHead>Owner</TableHead>
                                    <TableHead>Commission</TableHead>
                                    <TableHead>Products</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {vendors.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-10 text-center text-muted-foreground">
                                            No vendors found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    vendors.data.map((v) => (
                                        <TableRow key={v.id}>
                                            <TableCell className="font-medium">{v.store_name}</TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {v.owner}
                                                <span className="block text-xs">{v.email}</span>
                                            </TableCell>
                                            <TableCell>{Math.round(v.commission_rate * 100)}%</TableCell>
                                            <TableCell>{v.products}</TableCell>
                                            <TableCell>
                                                <Badge variant={STATUS_VARIANT[v.status] ?? 'secondary'}>
                                                    {v.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-2">
                                                    {v.status !== 'approved' && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() => act(v.id, 'approve')}
                                                        >
                                                            <Check className="h-4 w-4" /> Approve
                                                        </Button>
                                                    )}
                                                    {v.status !== 'suspended' && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => act(v.id, 'suspend')}
                                                        >
                                                            <Ban className="h-4 w-4" /> Suspend
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
