<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    public const TYPE_FOOD = 'food';

    public const TYPE_DRINK = 'drink';

    public const TYPE_BREAD = 'bread';

    public const TYPE_SALAD = 'salad';

    public const TYPE_SAUCE = 'sauce';

    protected $fillable = [
        'name',
        'type',
        'price',
        'stock_quantity',
        'unit',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
