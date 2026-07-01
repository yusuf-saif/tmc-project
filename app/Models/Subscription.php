<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'hijri_start_year',
        'hijri_start_month',
        'trial_ends_at',
        'cancelled_at',
        'suspended_at',
        'suspended_reason',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planName(): string
    {
        return match ($this->type) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annual' => 'Annual',
            default => 'Subscription',
        };
    }

    public function durationMonths(): int
    {
        return match ($this->type) {
            'monthly' => 1,
            'quarterly' => 3,
            'annual' => 12,
            default => 1,
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function daysUntilExpiry(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringBetween($query, $startDays, $endDays)
    {
        $now = now()->startOfDay();

        return $query->active()
            ->whereNotNull('end_date')
            ->where('end_date', '>=', $now->copy()->addDays($startDays))
            ->where('end_date', '<=', $now->copy()->addDays($endDays));
    }
}
