<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    //protects from mass assignment
    protected $fillable = [
        'user_id',
        'street_number',
        'street_name',
        'city',
        'state',
        'postcode',
        'country',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->street_number . ' ' . $this->street_name,
            $this->city,
            $this->state,
            $this->postcode,
            $this->country,
        ])->filter()->implode(', ');
    }
}
