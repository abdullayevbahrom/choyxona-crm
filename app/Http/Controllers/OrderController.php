<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\OrderAddItemRequest;
use App\Http\Requests\Orders\OrderCreateRequest;
use App\Http\Requests\Orders\OrderCreateStatusRequest;
use App\Http\Requests\Orders\OrderHistoryRequest;
use App\Http\Requests\Orders\OrderStoreRequest;
use App\Http\Requests\Orders\OrderUpdateItemRequest;
use App\Models\Bill;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Services\OrderService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function create(OrderCreateRequest $request): View
    {
        $validated = $request->validated();

        $room = Room::query()->findOrFail($validated['room']);

        $query = MenuItem::query()
            ->select(['id', 'name', 'type', 'price', 'is_active'])
            ->where('is_active', true)
            ->orderBy('name');

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['q'])) {
            $query->where('name', 'like', '%'.$validated['q'].'%');
        }

        $menuItems = $query->limit(100)->get();

        $openOrder = $room
            ->openOrder()
            ->first([
                'id',
                'room_id',
                'order_number',
                'status',
                'total_amount',
                'opened_at',
                'updated_at',
            ]);

        return view('orders.create', [
            'room' => $room,
            'menuItems' => $menuItems,
            'openOrder' => $openOrder,
            'filters' => $validated,
        ]);
    }

    public function store(OrderStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $room = Room::query()->findOrFail($validated['room_id']);

        try {
            $order = $this->orderService->createOrder(
                $room,
                auth()->id(),
                $validated['notes'] ?? null,
            );
            $this->orderService->attachServingWaiter($order, auth()->id());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'room_id' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log('orders.create', $order, 'Buyurtma ochildi.');

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order): View
    {
        $order->load([
            'room:id,number,name,status',
            'items:id,order_id,menu_item_id,quantity,unit_price,subtotal,notes,updated_at',
            'items.menuItem:id,name,type,is_active',
            'items.waiters:id,name',
            'bill:id,order_id,bill_number,subtotal,discount_percent,discount_amount,total_amount,payment_method,is_printed,updated_at',
            'waiters:id,name',
        ]);

        return view('orders.show', compact('order'));
    }

    public function panel(
        \Illuminate\Http\Request $request,
        Order $order,
    ): Response {
        $etag = $this->orderPanelEtagFromDatabase($order);
        $notModified = response('', 200);
        $notModified->setEtag($etag);

        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        $order->load([
            'room:id,number,name,status',
            'items:id,order_id,menu_item_id,quantity,unit_price,subtotal,notes,updated_at',
            'items.menuItem:id,name,type,is_active',
            'items.waiters:id,name',
            'bill:id,order_id,bill_number,subtotal,discount_percent,discount_amount,total_amount,payment_method,is_printed,updated_at',
            'waiters:id,name',
        ]);

        $response = response()->view(
            'orders.partials.order_panel',
            compact('order'),
        );
        $response->setEtag($etag);

        return $response;
    }

    public function panelFingerprint(Order $order): JsonResponse
    {
        return response()->json([
            'fingerprint' => $this->orderPanelEtagFromDatabase($order),
        ]);
    }

    public function createStatus(OrderCreateStatusRequest $request): Response
    {
        $validated = $request->validated();

        $room = Room::query()->findOrFail($validated['room']);
        $openOrder = $this->roomOpenOrderSnapshot($room);
        $etag = $this->createStatusEtag($room, $openOrder);
        $notModified = response('', 200);
        $notModified->setEtag($etag);

        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        $response = response()->view('orders.partials.create_status', [
            'room' => $room,
            'openOrder' => $openOrder,
        ]);
        $response->setEtag($etag);

        return $response;
    }

    public function createStatusFingerprint(
        OrderCreateStatusRequest $request,
    ): JsonResponse {
        $validated = $request->validated();

        $room = Room::query()->findOrFail($validated['room']);
        $openOrder = $this->roomOpenOrderSnapshot($room);

        return response()->json([
            'fingerprint' => $this->createStatusEtag($room, $openOrder),
        ]);
    }

    public function addItem(
        OrderAddItemRequest $request,
        Order $order,
    ): RedirectResponse {
        $validated = $request->validated();

        $menuItem = MenuItem::query()->findOrFail($validated['menu_item_id']);

        try {
            $this->orderService->addItem(
                $order,
                $menuItem,
                (int) ($validated['quantity'] ?? 1),
                $validated['notes'] ?? null,
            );
            $this->orderService->attachServingWaiter($order, auth()->id());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'menu_item_id' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            'orders.items.add',
            $order,
            "Buyurtmaga mahsulot qo'shildi.",
            [
                'menu_item_id' => $menuItem->id,
                'quantity' => (int) ($validated['quantity'] ?? 1),
            ],
        );

        $redirect = back()->with('status', 'Mahsulot qo\'shildi.');

        if ($menuItem->price === null) {
            $redirect->with(
                'warning',
                "Diqqat: mahsulot narxi kiritilmagan. Buyurtmaga 0 narx bilan qo'shildi.",
            );
        }

        return $redirect;
    }

    public function updateItem(
        OrderUpdateItemRequest $request,
        Order $order,
        OrderItem $item,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $this->orderService->updateItemQuantity(
                $order,
                $item,
                (int) $validated['quantity'],
            );
            $this->orderService->attachServingWaiter($order, auth()->id());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'quantity' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            'orders.items.update',
            $order,
            'Buyurtma mahsuloti miqdori yangilandi.',
            [
                'order_item_id' => $item->id,
                'quantity' => (int) $validated['quantity'],
            ],
        );

        return back()->with('status', 'Mahsulot miqdori yangilandi.');
    }

    public function removeItem(Order $order, OrderItem $item): RedirectResponse
    {
        try {
            $this->orderService->removeItem($order, $item);
            $this->orderService->attachServingWaiter($order, auth()->id());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'item' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            'orders.items.remove',
            $order,
            'Buyurtmadan mahsulot olib tashlandi.',
            [
                'order_item_id' => $item->id,
            ],
        );

        return back()->with('status', 'Mahsulot buyurtmadan olib tashlandi.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        try {
            $this->orderService->cancelOrder($order);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'order' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log('orders.cancel', $order, 'Buyurtma bekor qilindi.');

        return redirect()
            ->route('dashboard')
            ->with('status', 'Buyurtma bekor qilindi.');
    }

    public function history(OrderHistoryRequest $request): View
    {
        $validated = $request->validated();

        $query = Order::query()
            ->with([
                'room',
                'user',
                'waiters:id,name',
                'items:id,order_id',
                'items.waiters:id,name',
                'bill:id,order_id,bill_number,is_printed',
            ])
            ->whereIn('status', [
                Order::STATUS_OPEN,
                Order::STATUS_CLOSED,
                Order::STATUS_CANCELLED,
            ])
            ->orderByRaw('coalesce(closed_at, opened_at) desc')
            ->latest('id');

        if (! empty($validated['room_id'])) {
            $query->where('room_id', $validated['room_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereRaw('coalesce(closed_at, opened_at) >= ?', [
                $validated['date_from'].' 00:00:00',
            ]);
        }

        if (! empty($validated['date_to'])) {
            $query->whereRaw('coalesce(closed_at, opened_at) <= ?', [
                $validated['date_to'].' 23:59:59',
            ]);
        }

        $perPage =
            (int) ($validated['per_page'] ??
                config('pagination.default_per_page', 10));

        $orders = $query->paginate($perPage)->withQueryString();
        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get(['id', 'number']);

        return view('orders.history', [
            'orders' => $orders,
            'rooms' => $rooms,
            'filters' => $validated,
            'perPageOptions' => config('pagination.allowed_per_page'),
        ]);
    }

    private function orderPanelEtagFromDatabase(Order $order): string
    {
        $snapshot = Order::query()
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.id', $order->id)
            ->selectRaw(
                'orders.id as id, orders.status as status, orders.total_amount as total_amount, orders.updated_at as order_updated_at, count(order_items.id) as items_count, max(order_items.updated_at) as items_max_updated',
            )
            ->groupBy(
                'orders.id',
                'orders.status',
                'orders.total_amount',
                'orders.updated_at',
            )
            ->firstOrFail();

        $billSnapshot = Bill::query()
            ->where('order_id', $order->id)
            ->select([
                'updated_at',
                'discount_percent',
                'discount_amount',
                'total_amount',
                'is_printed',
            ])
            ->first();

        $orderUpdatedAtTs = ! empty($snapshot->order_updated_at)
            ? (int) strtotime((string) $snapshot->order_updated_at)
            : 0;

        return sha1(
            $snapshot->id.
                '|'.
                (string) $orderUpdatedAtTs.
                '|'.
                $snapshot->status.
                '|'.
                (string) $snapshot->total_amount.
                '|'.
                (string) ($snapshot->items_max_updated ?? '0').
                '|'.
                (string) ((int) ($snapshot->items_count ?? 0)).
                '|'.
                (string) ($billSnapshot?->updated_at?->timestamp ?? 0).
                '|'.
                (string) ($billSnapshot?->discount_percent ?? '0').
                '|'.
                (string) ($billSnapshot?->discount_amount ?? '0').
                '|'.
                (string) ($billSnapshot?->total_amount ?? '0').
                '|'.
                (string) ((int) ($billSnapshot?->is_printed ?? 0)),
        );
    }

    private function roomOpenOrderSnapshot(Room $room): ?Order
    {
        return $room
            ->openOrder()
            ->first([
                'id',
                'room_id',
                'order_number',
                'status',
                'total_amount',
                'opened_at',
                'updated_at',
            ]);
    }

    private function createStatusEtag(Room $room, ?Order $openOrder): string
    {
        $roomUpdated = (string) ($room->updated_at?->timestamp ?? 0);

        if (! $openOrder) {
            return sha1($room->id.'|'.$roomUpdated.'|none');
        }

        return sha1(
            $room->id.
                '|'.
                $roomUpdated.
                '|'.
                $openOrder->id.
                '|'.
                (string) ($openOrder->updated_at?->timestamp ?? 0).
                '|'.
                (string) $openOrder->total_amount,
        );
    }
}
