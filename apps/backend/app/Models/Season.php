<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_active'  => 'boolean',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Active cette saison et désactive toutes les autres.
     */
    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
