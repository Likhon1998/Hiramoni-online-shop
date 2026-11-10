<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierConsignment extends Model
{
    public const TYPE_FORWARD = 'forward';

    public const TYPE_RETURN = 'return';

    public const STATUS_PENDING = 'pending';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_BOOKED,
        self::STATUS_IN_TRANSIT,
    ];

    protected $fillable = [
        'shop_id',
        'order_id',
        'courier_service_id',
        'type',
        'driver',
        'tracking_number',
        'consignment_id',
        'invoice_ref',
        'status',
        'courier_status',
        'is_remote',
        'payload',
        'last_error',
        'booked_at',
        'delivered_at',
        'failed_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'payload' => 'array',
        'booked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courierService(): BelongsTo
    {
        return $this->belongsTo(CourierService::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isFailure(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_RETURNED, self::STATUS_CANCELLED], true);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES)->whereNotNull('tracking_number');
    }

    public function scopeForward($query)
    {
        return $query->where('type', self::TYPE_FORWARD);
    }

    public function scopeReturns($query)
    {
        return $query->where('type', self::TYPE_RETURN);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_BOOKED => 'Booked',
            self::STATUS_IN_TRANSIT => 'In transit',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
