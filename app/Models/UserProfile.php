<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'first_name',
        'last_name',
        'nickname',
        'country',
        'state',
        'outside_nigeria_location',
        'age_group',
        'marital_status',
        'phone',
        'instagram_username',
        'facebook_username',
        'x_username',
        'tiktok_username',
        'avatar_path',
        'notification_preferences',
        'goals',
        'onboarding_completed_at',
        'membership_id',
        'membership_type',
        'membership_status',
        'membership_serial',
        'membership_hijri_year',
        'application_submitted_at',
        'approved_at',
        'approved_by',
        'membership_fee_paid_at',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
            'goals' => 'array',
            'onboarding_completed_at' => 'datetime',
            'application_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'membership_fee_paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
