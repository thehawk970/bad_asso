<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['player_id', 'season_id', 'status'])]
class License extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Validated);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Pending);
    }

    public function scopeForSeason(Builder $query, Season $season): Builder
    {
        return $query->where('season_id', $season->id);
    }
}
