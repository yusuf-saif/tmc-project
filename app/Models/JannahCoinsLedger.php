<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JannahCoinsLedger extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'jannah_coins_ledger';

    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'amount',
        'reference_id',
        'admin_note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
