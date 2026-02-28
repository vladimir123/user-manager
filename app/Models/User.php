<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'external_id',
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'username',
        'gender',
        'date_of_birth',
        'nationality',
        'picture_large',
        'picture_thumbnail',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
