<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case CREATED = 'created';
    case PAID = 'paid';
    case DELIVERED = 'delivered';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';
    case DISPATCHED = 'dispatched';
    case REJECTED = 'rejected';

    public function labels(): array
    {
        return match ($this)
        {
            self::CREATED => __('Order Created'),
            self::PAID => __('Order Paid'),
            self::DELIVERED => __('Order Delivered'),
            self::REFUNDED => __('Order Refunded'),
            self::CANCELLED => __('Order Cancelled'),
            self::DISPATCHED => __('Order Dispatched'),
            self::REJECTED => __('Order Rejected'),
        };
    }
}
