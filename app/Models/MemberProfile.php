<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'onboarding_step',
        'onboarding_status',
        'membership_type',
        'membership_id',
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
    ];

    protected function casts(): array
    {
        return [
            'onboarding_step' => 'integer',
            'hijri_year' => 'integer',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'next_due_at' => 'datetime',
            'under_review_email_sent_at' => 'datetime',
            'approval_email_sent_at' => 'datetime',
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
}
