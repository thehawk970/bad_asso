<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $full_name
 * @property-read bool $has_valid_license
 * @property-read bool $has_valid_payment
 */
class Player extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'phone'];

    // ─── Relations ──────────────────────────────────────────────────────────────

    /** @return HasMany<License, $this> */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────────

    /** @return Attribute<string, never> */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->first_name} {$this->last_name}",
        );
    }

    /** @return Attribute<bool, never> */
    protected function hasValidLicense(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->licenses()
                ->where('status', LicenseStatus::Validated->value)
                ->exists(),
        );
    }

    /** @return Attribute<bool, never> */
    protected function hasValidPayment(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->payments()
                ->where('status', PaymentStatus::Validated->value)
                ->exists(),
        );
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Player>  $query
     * @return Builder<Player>
     */
    public function scopeWithPendingPayments(Builder $query): Builder
    {
        return $query->whereHas('payments', function (Builder $q) {
            $q->where('status', PaymentStatus::Pending->value);
        });
    }

    /**
     * @param  Builder<Player>  $query
     * @return Builder<Player>
     */
    public function scopeWithoutValidatedLicense(Builder $query): Builder
    {
        return $query->whereDoesntHave('licenses', function (Builder $q) {
            $q->where('status', LicenseStatus::Validated->value);
        });
    }

    /**
     * @param  Builder<Player>  $query
     * @return Builder<Player>
     */
    public function scopeWithoutAnyLicense(Builder $query): Builder
    {
        return $query->whereDoesntHave('licenses');
    }

    /**
     * @param  Builder<Player>  $query
     * @return Builder<Player>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%");
        });
    }
}
