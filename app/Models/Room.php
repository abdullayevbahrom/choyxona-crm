<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    public const STATUS_EMPTY = 'empty';
    public const STATUS_OCCUPIED = 'occupied';

    protected $fillable = [
        'number',
        'name',
        'capacity',
        'status',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function openOrder(): HasOne
    {
        return $this->hasOne(Order::class)->where('status', Order::STATUS_OPEN);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
