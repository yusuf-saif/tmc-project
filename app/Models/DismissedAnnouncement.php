<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DismissedAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'in_app_announcement_id',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inAppAnnouncement(): BelongsTo
    {
        return $this->belongsTo(InAppAnnouncement::class);
    }
}
