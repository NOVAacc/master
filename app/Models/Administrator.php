<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Administrator extends Authenticatable
{
    protected $hidden = ['password', 'remember_token'];
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
