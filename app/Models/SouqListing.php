<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SouqListing extends Model
{
    use HasFactory;

    public const CATEGORY_OPTIONS = [
        'fashion' => 'Fashion',
        'food_catering' => 'Food & Catering',
        'health_beauty' => 'Health & Beauty',
        'education' => 'Education',
        'services' => 'Services',
        'creative' => 'Creative',
        'other' => 'Other',
    ];

    public const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'category',
        'description',
        'contact_email',
        'phone',
        'website',
        'instagram',
        'logo_path',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $listing): void {
            if (blank($listing->slug)) {
                $listing->slug = static::generateUniqueSlug($listing->business_name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_OPTIONS[$this->category] ?? Str::headline($this->category);
    }

    protected static function generateUniqueSlug(string $businessName): string
    {
        $baseSlug = Str::slug($businessName);
        $slug = $baseSlug;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
