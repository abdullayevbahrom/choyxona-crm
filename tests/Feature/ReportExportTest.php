<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_export_reports_in_all_formats(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $csv = $this->actingAs($manager)->get('/reports/export.csv');
        $xls = $this->actingAs($manager)->get('/reports/export.xls');
        $pdf = $this->actingAs($manager)->get('/reports/export.pdf');

        $csv->assertOk();
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $xls->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $xls->headers->get('content-type'),
        );
        $this->assertStringContainsString(
            '.xlsx',
            (string) $xls->headers->get('content-disposition'),
        );

        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_cashier_cannot_export_reports(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $csv = $this->actingAs($cashier)->get('/reports/export.csv');
        $xls = $this->actingAs($cashier)->get('/reports/export.xls');
        $pdf = $this->actingAs($cashier)->get('/reports/export.pdf');

        $csv->assertForbidden();
        $xls->assertForbidden();
        $pdf->assertForbidden();
    }
}
