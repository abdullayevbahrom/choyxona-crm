<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Services\OrderService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function create(Request $request): View
    {
        $validated = $request->validate([
            "room" => ["required", "integer", "exists:rooms,id"],
            "type" => ["nullable", "in:food,drink,bread,salad,sauce"],
            "q" => ["nullable", "string", "max:200"],
        ]);

        $room = Room::query()->findOrFail($validated["room"]);

        $query = MenuItem::query()->where("is_active", true)->orderBy("name");

        if (!empty($validated["type"])) {
            $query->where("type", $validated["type"]);
        }

        if (!empty($validated["q"])) {
            $query->where("name", "like", "%" . $validated["q"] . "%");
        }

        $menuItems = $query->limit(100)->get();

        $openOrder = $room->openOrder()->with("items.menuItem")->first();

        return view("orders.create", [
            "room" => $room,
            "menuItems" => $menuItems,
            "openOrder" => $openOrder,
            "filters" => $validated,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            "room_id" => ["required", "integer", "exists:rooms,id"],
            "notes" => ["nullable", "string"],
        ]);

        $room = Room::query()->findOrFail($validated["room_id"]);

        try {
            $order = $this->orderService->createOrder(
                $room,
                auth()->id(),
                $validated["notes"] ?? null,
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "room_id" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log("orders.create", $order, "Buyurtma ochildi.");

        return redirect()->route("orders.show", $order);
    }

    public function show(Order $order): View
    {
        $order->load(["room", "items.menuItem"]);

        return view("orders.show", compact("order"));
    }

    public function panel(Request $request, Order $order): Response
    {
        $order->load(["room", "items.menuItem"]);

        $response = response()->view(
            "orders.partials.order_panel",
            compact("order"),
        );
        $response->setEtag($this->orderPanelEtag($order));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    public function panelFingerprint(Order $order): JsonResponse
    {
        return response()->json([
            "fingerprint" => $this->orderPanelEtag($order),
        ]);
    }

    public function createStatus(Request $request): Response
    {
        $validated = $request->validate([
            "room" => ["required", "integer", "exists:rooms,id"],
        ]);

        $room = Room::query()->findOrFail($validated["room"]);
        $openOrder = $room->openOrder()->with("items.menuItem")->first();

        $response = response()->view("orders.partials.create_status", [
            "room" => $room,
            "openOrder" => $openOrder,
        ]);
        $response->setEtag($this->createStatusEtag($room, $openOrder));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    public function createStatusFingerprint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "room" => ["required", "integer", "exists:rooms,id"],
        ]);

        $room = Room::query()->findOrFail($validated["room"]);
        $openOrder = $room->openOrder()->first();

        return response()->json([
            "fingerprint" => $this->createStatusEtag($room, $openOrder),
        ]);
    }

    public function addItem(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            "menu_item_id" => ["required", "integer", "exists:menu_items,id"],
            "quantity" => ["nullable", "integer", "min:1", "max:1000"],
            "notes" => ["nullable", "string", "max:500"],
        ]);

        $menuItem = MenuItem::query()->findOrFail($validated["menu_item_id"]);

        try {
            $this->orderService->addItem(
                $order,
                $menuItem,
                (int) ($validated["quantity"] ?? 1),
                $validated["notes"] ?? null,
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "menu_item_id" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            "orders.items.add",
            $order,
            "Buyurtmaga mahsulot qo'shildi.",
            [
                "menu_item_id" => $menuItem->id,
                "quantity" => (int) ($validated["quantity"] ?? 1),
            ],
        );

        $redirect = back()->with("status", 'Mahsulot qo\'shildi.');

        if ($menuItem->price === null) {
            $redirect->with(
                "warning",
                "Diqqat: mahsulot narxi kiritilmagan. Buyurtmaga 0 narx bilan qo'shildi.",
            );
        }

        return $redirect;
    }

    public function updateItem(
        Request $request,
        Order $order,
        OrderItem $item,
    ): RedirectResponse {
        $validated = $request->validate([
            "quantity" => ["required", "integer", "min:1", "max:1000"],
        ]);

        try {
            $this->orderService->updateItemQuantity(
                $order,
                $item,
                (int) $validated["quantity"],
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "quantity" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            "orders.items.update",
            $order,
            "Buyurtma mahsuloti miqdori yangilandi.",
            [
                "order_item_id" => $item->id,
                "quantity" => (int) $validated["quantity"],
            ],
        );

        return back()->with("status", "Mahsulot miqdori yangilandi.");
    }

    public function removeItem(Order $order, OrderItem $item): RedirectResponse
    {
        try {
            $this->orderService->removeItem($order, $item);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "item" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log(
            "orders.items.remove",
            $order,
            "Buyurtmadan mahsulot olib tashlandi.",
            [
                "order_item_id" => $item->id,
            ],
        );

        return back()->with("status", "Mahsulot buyurtmadan olib tashlandi.");
    }

    public function cancel(Order $order): RedirectResponse
    {
        try {
            $this->orderService->cancelOrder($order);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "order" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log("orders.cancel", $order, "Buyurtma bekor qilindi.");

        return redirect()
            ->route("dashboard")
            ->with("status", "Buyurtma bekor qilindi.");
    }

    public function history(Request $request): View
    {
        $validated = $request->validate([
            "room_id" => ["nullable", "integer", "exists:rooms,id"],
            "date_from" => ["nullable", "date"],
            "date_to" => ["nullable", "date"],
            "status" => ["nullable", "in:closed,cancelled"],
        ]);

        $query = Order::query()
            ->with(["room", "user"])
            ->whereIn("status", ["closed", "cancelled"])
            ->latest("closed_at")
            ->latest("id");

        if (!empty($validated["room_id"])) {
            $query->where("room_id", $validated["room_id"]);
        }

        if (!empty($validated["status"])) {
            $query->where("status", $validated["status"]);
        }

        if (!empty($validated["date_from"])) {
            $query->whereDate("closed_at", ">=", $validated["date_from"]);
        }

        if (!empty($validated["date_to"])) {
            $query->whereDate("closed_at", "<=", $validated["date_to"]);
        }

        $orders = $query->paginate(30)->withQueryString();
        $rooms = Room::query()
            ->where("is_active", true)
            ->orderBy("number")
            ->get(["id", "number"]);

        return view("orders.history", [
            "orders" => $orders,
            "rooms" => $rooms,
            "filters" => $validated,
        ]);
    }

    private function orderPanelEtag(Order $order): string
    {
        $itemsMaxUpdated = (string) ($order->items()->max("updated_at") ?? "0");
        $itemsCount = (string) $order->items()->count();
        $orderUpdated = (string) ($order->updated_at?->timestamp ?? 0);

        return sha1(
            $order->id .
                "|" .
                $orderUpdated .
                "|" .
                $order->status .
                "|" .
                (string) $order->total_amount .
                "|" .
                $itemsMaxUpdated .
                "|" .
                $itemsCount,
        );
    }

    private function createStatusEtag(Room $room, ?Order $openOrder): string
    {
        $roomUpdated = (string) ($room->updated_at?->timestamp ?? 0);

        if (!$openOrder) {
            return sha1($room->id . "|" . $roomUpdated . "|none");
        }

        return sha1(
            $room->id .
                "|" .
                $roomUpdated .
                "|" .
                $openOrder->id .
                "|" .
                (string) ($openOrder->updated_at?->timestamp ?? 0) .
                "|" .
                (string) $openOrder->total_amount,
        );
    }
}
