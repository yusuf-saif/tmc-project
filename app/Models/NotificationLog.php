<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'title',
        'body',
        'audience_type',
        'audience_value',
        'sent_by',
        'sent_at',
        'delivery_count',
    ];

    protected function casts(): array
    {
        return [
            'audience_value' => 'array',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
