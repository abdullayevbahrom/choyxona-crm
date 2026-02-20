<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollingEtagTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cards_returns_304_when_etag_matches(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        Room::query()->create([
            "number" => "701",
            "status" => Room::STATUS_EMPTY,
            "is_active" => true,
        ]);

        $first = $this->actingAs($cashier)->get(route("dashboard.cards"));
        $first->assertOk();

        $etag = $first->headers->get("etag");
        $this->assertNotNull($etag);

        $second = $this->actingAs($cashier)
            ->withHeaders([
                "If-None-Match" => $etag,
            ])
            ->get(route("dashboard.cards"));

        $second->assertStatus(304);
    }

    public function test_dashboard_fingerprint_changes_when_room_state_changes(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $room = Room::query()->create([
            "number" => "702",
            "status" => Room::STATUS_EMPTY,
            "is_active" => true,
        ]);

        $first = $this->actingAs($cashier)->get(route("dashboard.fingerprint"));
        $first->assertOk();

        $firstFingerprint = $first->json("fingerprint");
        $this->assertNotEmpty($firstFingerprint);

        $room->update([
            "status" => Room::STATUS_OCCUPIED,
        ]);

        $second = $this->actingAs($cashier)->get(
            route("dashboard.fingerprint"),
        );
        $second->assertOk();

        $secondFingerprint = $second->json("fingerprint");
        $this->assertNotEmpty($secondFingerprint);
        $this->assertNotSame($firstFingerprint, $secondFingerprint);
    }
}
