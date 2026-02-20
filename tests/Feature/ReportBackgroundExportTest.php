<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportBackgroundExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_request_background_report_export(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->post("/reports/exports", [
            "date_from" => now()->toDateString(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas("report_exports", [
            "user_id" => $manager->id,
            "status" => ReportExport::STATUS_PENDING,
            "format" => "csv",
        ]);

        Queue::assertPushed(GenerateReportExport::class);
    }

    public function test_cashier_cannot_request_background_report_export(): void
    {
        Queue::fake();

        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->post("/reports/exports");

        $response->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_owner_can_download_ready_report_export_file(): void
    {
        Storage::fake("local");

        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $path = "exports/reports-ready.csv";
        Storage::disk("local")->put($path, "a,b,c\n1,2,3");

        $export = ReportExport::query()->create([
            "user_id" => $manager->id,
            "status" => ReportExport::STATUS_READY,
            "filters" => [],
            "format" => "csv",
            "file_path" => $path,
            "file_size" => 11,
            "finished_at" => now(),
        ]);

        $response = $this->actingAs($manager)->get(
            route("reports.exports.download", $export),
        );

        $response->assertOk();
        $response->assertHeader("content-type", "text/csv; charset=UTF-8");
    }

    public function test_owner_can_view_report_export_status_json(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $export = ReportExport::query()->create([
            "user_id" => $manager->id,
            "status" => ReportExport::STATUS_PROCESSING,
            "filters" => [],
            "format" => "csv",
        ]);

        $response = $this->actingAs($manager)->getJson(
            route("reports.exports.status", $export),
        );

        $response->assertOk();
        $response->assertJson([
            "id" => $export->id,
            "status" => ReportExport::STATUS_PROCESSING,
            "download_url" => null,
        ]);
    }

    public function test_non_owner_cannot_view_report_export_status_json(): void
    {
        $owner = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);
        $other = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $export = ReportExport::query()->create([
            "user_id" => $owner->id,
            "status" => ReportExport::STATUS_PENDING,
            "filters" => [],
            "format" => "csv",
        ]);

        $response = $this->actingAs($other)->getJson(
            route("reports.exports.status", $export),
        );

        $response->assertForbidden();
    }
}
