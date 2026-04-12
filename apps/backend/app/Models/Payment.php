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

#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    protected $fillable = ['player_id', 'order_id', 'amount', 'method', 'status', 'reference'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Validated);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Pending);
    }
}
