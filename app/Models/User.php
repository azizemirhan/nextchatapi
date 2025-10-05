<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->api_key) {
                $user->api_key = bin2hex(random_bytes(32));
            }
        });
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }
}
