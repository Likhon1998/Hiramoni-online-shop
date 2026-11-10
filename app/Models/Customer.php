<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'name',
        'email',
        'phone',
        'phone_country_code',
        'address',
        'date_of_birth',
        'gender',
        'reward_points',
        'baki_balance',
        'emi_balance',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'baki_balance' => 'decimal:2',
            'emi_balance' => 'decimal:2',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->latest('id');
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class)->latest('id');
    }

    public function bakiEntries()
    {
        return $this->hasMany(CustomerBakiEntry::class)->latest();
    }

    public function emiPlans()
    {
        return $this->hasMany(CustomerEmiPlan::class)->latest();
    }

    public function emiEntries()
    {
        return $this->hasMany(CustomerEmiEntry::class)->latest();
    }

    // Relationship to the Shop
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class)->latest();
    }

    /** Match phone allowing formatting differences (spaces, +, dashes). */
    public function scopeWherePhone($query, ?string $phone)
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return $query->whereRaw('1 = 0');
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;

        return $query->where(function ($q) use ($raw, $digits) {
            $q->where('phone', $raw)->orWhere('phone', $digits);

            if (strlen($digits) >= 10) {
                $tail = substr($digits, -10);
                $q->orWhere('phone', 'like', '%'.$tail);
            } elseif (strlen($digits) >= 9) {
                $q->orWhere('phone', 'like', '%'.$digits);
            }
        });
    }

    public static function normalizePhone(?string $phone): string
    {
        $raw = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;

        // +8801711000000 / 8801711000000 → 01711000000
        return str_starts_with($digits, '880') && strlen($digits) === 13 ? substr($digits, 2) : $digits;
    }

    public function ensureLegacyAddressRecord(): void
    {
        $line = trim((string) $this->address);
        if ($line === '' || $this->addresses()->exists()) {
            return;
        }

        $this->addresses()->create([
            'label' => 'Home',
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $line,
            'is_default' => true,
        ]);
    }

    public function rememberDeliveryAddress(array $contact): void
    {
        $line = trim(preg_replace('/\s+/u', ' ', (string) ($contact['address'] ?? '')) ?? '');
        if ($line === '') {
            return;
        }

        $this->ensureLegacyAddressRecord();

        $match = $this->addresses()->get()->first(
            fn (CustomerAddress $row) => mb_strtolower(trim((string) $row->address)) === mb_strtolower($line)
        );

        if ($match) {
            $match->update([
                'name' => $contact['name'] ?? $match->name,
                'phone' => $contact['phone'] ?? $match->phone,
            ]);

            return;
        }

        $isFirst = $this->addresses()->doesntExist();
        $this->addresses()->create([
            'label' => $isFirst ? 'Home' : 'Address '.($this->addresses()->count() + 1),
            'name' => $contact['name'] ?? $this->name,
            'phone' => $contact['phone'] ?? $this->phone,
            'address' => $line,
            'is_default' => $isFirst,
        ]);

        if ($isFirst && trim((string) $this->address) === '') {
            $this->forceFill(['address' => $line])->save();
        }
    }

    public function makeDefaultAddress(CustomerAddress $address): void
    {
        $this->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        $this->forceFill(['address' => $address->address])->save();
    }

    public function block(?string $reason = null): void
    {
        $this->forceFill([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason !== null && $reason !== '' ? mb_substr(trim($reason), 0, 255) : null,
        ])->save();
    }

    public function unblock(): void
    {
        $this->forceFill([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ])->save();
    }
}