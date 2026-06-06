<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoleHistory extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'user_role_history';

    protected $fillable = [
        'user_id',
        'changed_by',
        'old_role',
        'new_role',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
