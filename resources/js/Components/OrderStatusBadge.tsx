import { Badge, BadgeProps } from '@/Components/ui/badge';

const VARIANTS: Record<string, BadgeProps['variant']> = {
    pending: 'warning',
    paid: 'default',
    fulfilled: 'success',
    cancelled: 'destructive',
    refunded: 'secondary',
};

export default function OrderStatusBadge({ status }: { status: string }) {
    const label = status.charAt(0).toUpperCase() + status.slice(1);

    return <Badge variant={VARIANTS[status] ?? 'secondary'}>{label}</Badge>;
}
