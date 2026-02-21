<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rooms\RoomIndexRequest;
use App\Http\Requests\Rooms\RoomStoreRequest;
use App\Http\Requests\Rooms\RoomUpdateRequest;
use App\Models\Order;
use App\Models\Room;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function dashboard(): View
    {
        $rooms = $this->dashboardRooms();

        return view('rooms.dashboard', compact('rooms'));
    }

    public function dashboardCards(Request $request): Response
    {
        $etag = $this->dashboardCardsEtag();
        $response = response('', 200);
        $response->setEtag($etag);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $rooms = $this->dashboardRooms();
        $freshResponse = response()->view(
            'rooms.partials.cards',
            compact('rooms'),
        );
        $freshResponse->setEtag($etag);

        return $freshResponse;
    }

    public function dashboardFingerprint(): JsonResponse
    {
        return response()->json([
            'fingerprint' => $this->dashboardCardsEtag(),
        ]);
    }

    public function index(RoomIndexRequest $request): View
    {
        $validated = $request->validated();
        $perPage =
            (int) ($validated['per_page'] ??
                config('pagination.default_per_page', 10));
        $rooms = Room::query()
            ->orderBy('number')
            ->paginate($perPage)
            ->withQueryString();

        return view('rooms.index', [
            'rooms' => $rooms,
            'filters' => $validated,
            'perPageOptions' => config('pagination.allowed_per_page'),
        ]);
    }

    public function store(RoomStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $room = Room::query()->create(
            $validated + [
                'status' => Room::STATUS_EMPTY,
                'is_active' => true,
            ],
        );
        ActivityLogger::log('rooms.create', $room, 'Xona yaratildi.');

        return redirect()
            ->route('rooms.index')
            ->with('status', 'Xona yaratildi.');
    }

    public function update(
        RoomUpdateRequest $request,
        Room $room,
    ): RedirectResponse {
        $validated = $request->validated();

        $room->update($validated);
        ActivityLogger::log('rooms.update', $room, 'Xona yangilandi.');

        return back()->with('status', 'Xona ma\'lumotlari yangilandi.');
    }

    public function toggleActive(Room $room): RedirectResponse
    {
        $room->update([
            'is_active' => ! $room->is_active,
        ]);
        ActivityLogger::log(
            'rooms.toggle_active',
            $room,
            'Xona faolligi almashtirildi.',
        );

        return back()->with('status', 'Xona holati yangilandi.');
    }

    private function dashboardRooms()
    {
        return Room::query()
            ->select([
                'id',
                'number',
                'name',
                'status',
                'is_active',
                'updated_at',
            ])
            ->where('is_active', true)
            ->with([
                'openOrder:id,room_id,order_number,status,total_amount,opened_at,updated_at',
            ])
            ->orderBy('number')
            ->get();
    }

    private function dashboardCardsEtag(): string
    {
        $roomAggregate = Room::query()
            ->where('is_active', true)
            ->selectRaw(
                'max(updated_at) as rooms_updated_at, sum(case when status = ? then 1 else 0 end) as occupied_rooms_count',
                [Room::STATUS_OCCUPIED],
            )
            ->first();

        $orderAggregate = Order::query()
            ->where('status', Order::STATUS_OPEN)
            ->selectRaw(
                'max(updated_at) as open_orders_updated_at, count(*) as open_orders_count',
            )
            ->first();

        $roomsUpdatedAt = (string) ($roomAggregate?->rooms_updated_at ?? '0');
        $occupiedRoomsCount = (string) ((int) ($roomAggregate?->occupied_rooms_count ??
            0));
        $openOrdersUpdatedAt =
            (string) ($orderAggregate?->open_orders_updated_at ?? '0');
        $openOrdersCount = (string) ((int) ($orderAggregate?->open_orders_count ??
            0));

        return sha1(
            $roomsUpdatedAt.
                '|'.
                $occupiedRoomsCount.
                '|'.
                $openOrdersUpdatedAt.
                '|'.
                $openOrdersCount,
        );
    }
}
