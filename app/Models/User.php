<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'phone_verified_at', 'password', 'password_initialized_at', 'failed_login_attempts', 'login_locked_until'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'phone_verified_at' => 'datetime', 'password_initialized_at' => 'datetime', 'login_locked_until' => 'datetime', 'password' => 'hashed'];
    }
}
