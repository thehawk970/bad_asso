<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PaymentStatus $status
 * @property PaymentMethod|null $method
 */
#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    protected $fillable = ['player_id', 'order_id', 'amount', 'method', 'status', 'reference'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Validated);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Pending);
    }
}
