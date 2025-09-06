<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'phone', 'password', 'role',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }
}