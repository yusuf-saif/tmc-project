<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon_path',
        'criteria',
        'is_active',
        'coin_reward',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'coin_reward' => 'integer',
        ];
    }

    public function getIconUrlAttribute(): ?string
    {
        $path = $this->icon_path;

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        try {
            return Storage::disk('r2')->url($path);
        } catch (\Throwable) {
            return $path;
        }
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
