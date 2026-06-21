<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'price',
        'description',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    const TYPES = ['monthly', 'quarterly', 'annual'];

    const DURATION_MONTHS = [
        'monthly' => 1,
        'quarterly' => 3,
        'annual' => 12,
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function durationMonths(): int
    {
        return self::DURATION_MONTHS[$this->type] ?? 1;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
