<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory;

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_TRANSFER = 'transfer';

    protected $fillable = [
        'order_id',
        'room_id',
        'bill_number',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'total_amount',
        'payment_method',
        'is_printed',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_printed' => 'boolean',
            'printed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
