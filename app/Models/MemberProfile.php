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
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_step' => 'integer',
            'hijri_year' => 'integer',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
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
}
