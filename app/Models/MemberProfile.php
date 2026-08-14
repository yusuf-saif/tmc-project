<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberProfile extends Model
{
    use HasFactory;

    protected $attributes = [
        'onboarding_status' => 'registered',
    ];

    protected $fillable = [
        'user_id',
        'display_name',
        'first_name',
        'last_name',
        'nickname',
        'location_country',
        'location_state',
        'location_international',
        'age_group',
        'marital_status',
        'phone',
        'ig_username',
        'fb_username',
        'x_username',
        'tiktok_username',
        'avatar_path',
        'notification_preferences',
        'goals',
        'onboarding_status',
        'membership_type',
        'membership_id',
        'membership_serial',
        'hijri_year',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'needs_correction_notes',
        'submitted_at',
        'approved_by',
        'approved_at',
        'payment_submitted_at',
        'payment_proof_path',
        'payment_verified_by',
        'payment_verified_at',
        'activated_at',
        'preferred_billing_cycle',
        'next_due_at',
        'paystack_reference',
        'paystack_customer_code',
        'payment_failed_reason',
        'payment_source',
        'payment_status',
        'hijri_join_date',
        'current_period_ends_at',
        'reminder_sent_at',
        'onboarding_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'hijri_year' => 'integer',
            'membership_serial' => 'integer',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'next_due_at' => 'datetime',
            'first_paid_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'notification_preferences' => 'array',
            'goals' => 'array',
            'under_review_email_sent_at' => 'datetime',
            'approval_email_sent_at' => 'datetime',
            'hijri_join_date' => 'datetime',
            'payment_confirmed_email_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }
}
