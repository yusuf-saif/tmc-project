<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MembershipOnboardingDraft extends Model
{
    protected $fillable = [
        'id',
        'payload',
        'step',
        'status',
        'referral_code',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'payload' => 'array',
            'step' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $draft) {
            if (empty($draft->id)) {
                $draft->id = (string) Str::uuid();
            }
        });
    }
}
