<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CourierService extends Model
{
    public const DRIVER_MANUAL = 'manual';

    public const DRIVER_STEADFAST = 'steadfast';

    public const DRIVER_PATHAO = 'pathao';

    public const DRIVER_REDX = 'redx';

    public const DRIVERS = [
        self::DRIVER_MANUAL => 'Manual (enter tracking)',
        self::DRIVER_STEADFAST => 'Steadfast API',
        self::DRIVER_PATHAO => 'Pathao Merchant API',
        self::DRIVER_REDX => 'RedX API',
    ];

    public const DEFAULT_BASE_URLS = [
        self::DRIVER_STEADFAST => 'https://portal.packzy.com/api/v1',
        self::DRIVER_PATHAO => 'https://api-hermes.pathao.com',
        self::DRIVER_REDX => 'https://openapi.redx.com.bd',
    ];

    protected $fillable = [
        'shop_id',
        'name',
        'driver',
        'api_base_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'extra_config',
        'phone',
        'notes',
        'sort_order',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'extra_config' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'webhook_secret',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(CourierConsignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function driverKey(): string
    {
        $driver = strtolower(trim((string) ($this->driver ?: self::DRIVER_MANUAL)));

        return array_key_exists($driver, self::DRIVERS) ? $driver : self::DRIVER_MANUAL;
    }

    public function baseUrl(): string
    {
        $custom = trim((string) ($this->api_base_url ?? ''));
        if ($custom !== '') {
            return rtrim($custom, '/');
        }

        return rtrim(self::DEFAULT_BASE_URLS[$this->driverKey()] ?? '', '/');
    }

    public function hasApiCredentials(): bool
    {
        $key = trim((string) ($this->api_key ?? ''));
        $secret = trim((string) ($this->api_secret ?? ''));

        return match ($this->driverKey()) {
            self::DRIVER_STEADFAST => $key !== '' && $secret !== '',
            self::DRIVER_PATHAO => $key !== '' && $secret !== '',
            self::DRIVER_REDX => $key !== '',
            default => false,
        };
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        $config = is_array($this->extra_config) ? $this->extra_config : [];

        return $config[$key] ?? $default;
    }

    public static function driverFromName(string $name): string
    {
        $slug = Str::lower(trim($name));

        return match (true) {
            str_contains($slug, 'steadfast') => self::DRIVER_STEADFAST,
            str_contains($slug, 'pathao') => self::DRIVER_PATHAO,
            str_contains($slug, 'redx') || str_contains($slug, 'red x') => self::DRIVER_REDX,
            default => self::DRIVER_MANUAL,
        };
    }

    /** Shipping requires a courier, so a new shop starts with the common local services. */
    public static function ensureDefaults(int $shopId): void
    {
        if (static::forShop($shopId)->exists()) {
            return;
        }

        $hasDriver = Schema::hasColumn((new static)->getTable(), 'driver');

        foreach (['Pathao', 'Steadfast', 'RedX', 'Paperfly'] as $i => $name) {
            $row = [
                'shop_id' => $shopId,
                'name' => $name,
                'sort_order' => $i + 1,
                'is_active' => true,
            ];
            if ($hasDriver) {
                $row['driver'] = static::driverFromName($name);
            }
            static::create($row);
        }
    }
}
