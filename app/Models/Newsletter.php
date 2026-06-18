<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'target_audience',
        'audience_value',
        'schedule_at',
        'status',
        'sent_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'audience_value' => 'array',
            'schedule_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeReadyToSend($query)
    {
        return $query->scheduled()
            ->where(function ($q) {
                $q->whereNull('schedule_at')->orWhere('schedule_at', '<=', now());
            });
    }
}
