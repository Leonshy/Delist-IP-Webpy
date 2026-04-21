<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IpUnblockLog extends Model
{
    protected $fillable = [
        'ip',
        'jail',
        'was_blocked',
        'turnstile_valid',
        'unblocked',
        'reason',
    ];
}
