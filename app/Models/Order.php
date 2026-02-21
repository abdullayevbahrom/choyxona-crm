<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'room_id',
        'order_number',
        'status',
        'total_amount',
        'notes',
        'opened_at',
        'closed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function waiters(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'order_waiters',
        )->withTimestamps();
    }

    public function servedWaiterNames(): Collection
    {
        $orderWaiterNames = ($this->relationLoaded('waiters')
            ? $this->waiters
            : $this->waiters()->get(['users.id', 'users.name'])
        )->pluck('name');

        $itemWaiterNames = ($this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('waiters:id,name')->get()
        )->flatMap(function (OrderItem $item) {
            $waiters = $item->relationLoaded('waiters')
                ? $item->waiters
                : $item->waiters()->get(['users.id', 'users.name']);

            return $waiters->pluck('name');
        });

        return $orderWaiterNames
            ->merge($itemWaiterNames)
            ->filter()
            ->unique()
            ->values();
    }
}
