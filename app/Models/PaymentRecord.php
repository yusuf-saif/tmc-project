<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_profile_id',
        'external_reference',
        'provider',
        'billing_cycle',
        'channel',
        'amount_kobo',
        'currency',
        'status',
        'failure_reason',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }
}
