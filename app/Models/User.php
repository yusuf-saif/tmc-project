<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'suspended_at',
        'suspended_reason',
        'referral_code',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'user_interests');
    }

    public function goals(): BelongsToMany
    {
        return $this->belongsToMany(Goal::class, 'user_goals');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function referralAwards(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    // Alias for checklist compatibility — list referrals where this user is the referrer
    public function referrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    public function referralRecord(): HasOne
    {
        return $this->hasOne(UserReferral::class, 'referred_id');
    }

    public function jannahCoinsLedger(): HasMany
    {
        return $this->hasMany(JannahCoinsLedger::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'admin',
            'moderator',
            'content_editor',
        ]);
    }
}
