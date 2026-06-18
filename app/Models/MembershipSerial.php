<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_type',
        'hijri_year',
        'last_serial',
    ];
}
