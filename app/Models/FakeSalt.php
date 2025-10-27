<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakeSalt extends Model
{
    protected $fillable = [
        'email',
        'salt',
    ];
}

