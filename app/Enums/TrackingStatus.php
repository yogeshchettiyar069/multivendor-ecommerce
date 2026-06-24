<?php

declare(strict_types=1);

namespace App\Enums;

enum TrackingStatus: string
{
    case Placed = 'placed';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Order Placed',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
        };
    }

    /**
     * Position in the timeline (0-based).
     */
    public function step(): int
    {
        return array_search($this, self::cases(), true) ?: 0;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
