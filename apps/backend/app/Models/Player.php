<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['first_name', 'last_name', 'email', 'phone'])]
class Player extends Model
{
    // ─── Relations ──────────────────────────────────────────────────────────────

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getHasValidLicenseAttribute(): bool
    {
        return $this->licenses()
            ->where('status', LicenseStatus::Validated->value)
            ->exists();
    }

    public function getHasValidPaymentAttribute(): bool
    {
        return $this->payments()
            ->where('status', PaymentStatus::Validated->value)
            ->exists();
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeWithPendingPayments(Builder $query): Builder
    {
        return $query->whereHas('payments', function (Builder $q) {
            $q->where('status', PaymentStatus::Pending->value);
        });
    }

    public function scopeWithoutValidatedLicense(Builder $query): Builder
    {
        return $query->whereDoesntHave('licenses', function (Builder $q) {
            $q->where('status', LicenseStatus::Validated->value);
        });
    }

    public function scopeWithoutAnyLicense(Builder $query): Builder
    {
        return $query->whereDoesntHave('licenses');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('first_name', 'ilike', "%{$search}%")
              ->orWhere('last_name', 'ilike', "%{$search}%")
              ->orWhere('email', 'ilike', "%{$search}%");
        });
    }
}
