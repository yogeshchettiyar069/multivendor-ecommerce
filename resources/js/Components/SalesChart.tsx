import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { formatCents } from '@/lib/format';
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface Point {
    label: string;
    cents: number;
}

const BRAND = '#7c3aed';

export default function SalesChart({ data }: { data: Point[] }) {
    const chartData = data.map((d) => ({ name: d.label, value: d.cents / 100 }));

    return (
        <Card>
            <CardHeader>
                <CardTitle>Sales — last 6 months</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-64 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={chartData} margin={{ top: 10, right: 8, left: -8, bottom: 0 }}>
                            <defs>
                                <linearGradient id="salesFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={BRAND} stopOpacity={0.35} />
                                    <stop offset="95%" stopColor={BRAND} stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                            <XAxis
                                dataKey="name"
                                tickLine={false}
                                axisLine={false}
                                tick={{ fill: '#94a3b8', fontSize: 12 }}
                            />
                            <YAxis
                                tickFormatter={(v: number) => `$${v}`}
                                tickLine={false}
                                axisLine={false}
                                width={56}
                                tick={{ fill: '#94a3b8', fontSize: 12 }}
                            />
                            <Tooltip
                                cursor={{ stroke: BRAND, strokeWidth: 1 }}
                                formatter={(value) => [
                                    formatCents(Math.round(Number(value) * 100)),
                                    'Sales',
                                ]}
                                contentStyle={{
                                    background: 'hsl(var(--popover))',
                                    border: '1px solid hsl(var(--border))',
                                    borderRadius: 8,
                                    color: 'hsl(var(--popover-foreground))',
                                    fontSize: 12,
                                }}
                            />
                            <Area
                                type="monotone"
                                dataKey="value"
                                stroke={BRAND}
                                strokeWidth={2}
                                fill="url(#salesFill)"
                                animationDuration={700}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
