<?php

namespace App\Models;

use App\Notifications\SetPasswordNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (User $user): void {
            if ($user->isDirty('email') && $user->email !== null) {
                $user->email = strtolower($user->email);
            }
        });
    }

    public function scopeWhereEmail($query, string $email)
    {
        return $query->whereRaw('LOWER(email) = LOWER(?)', [$email]);
    }

    /**
     * The attributes that are mass assignable.
     *
     * Sensitive fields (status, suspended_at, suspended_reason) are in
     * $fillable for service-layer mass assignment but must never be
     * exposed to direct user input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'invited_at',
        'member_id',
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
            'invited_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
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

    public function eventRsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function rsvpdEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_rsvps')
            ->withPivot(['rsvp_at', 'cancelled_at']);
    }

    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function souqListings(): HasMany
    {
        return $this->hasMany(SouqListing::class);
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot(['awarded_at', 'awarded_by']);
    }

    public function supportApplications(): HasMany
    {
        return $this->hasMany(SupportApplication::class);
    }

    public function roleHistory(): HasMany
    {
        return $this->hasMany(UserRoleHistory::class);
    }

    public function duaListItems(): HasMany
    {
        return $this->hasMany(DuaListItem::class);
    }

    public function dismissedAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(InAppAnnouncement::class, 'dismissed_announcements')
            ->withPivot('dismissed_at')
            ->withTimestamps();
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SetPasswordNotification($token));
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
