<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read float $amount_paid
 * @property-read float $remaining_amount
 */
#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    protected $fillable = ['player_id', 'status', 'total', 'reference', 'paid_at'];

    #[Override]
    protected function casts(): array
    {
        return [
            'status'  => OrderStatus::class,
            'total'   => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────────

    /** @return Attribute<float, never> */
    protected function amountPaid(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->payments()
                ->where('status', PaymentStatus::Validated->value)
                ->sum('amount'),
        );
    }

    /** @return Attribute<float, never> */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): float => max(0, (float) $this->total - $this->amount_paid),
        );
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /** @param Builder<Order> $query */
    public function scopePending(Builder $query): Builder<Order>
    {
        return $query->where('status', OrderStatus::Pending);
    }

    /** @param Builder<Order> $query */
    public function scopePaid(Builder $query): Builder<Order>
    {
        return $query->where('status', OrderStatus::Paid);
    }
}
