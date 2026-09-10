<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_tasks_excel_export_returns_file(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->count(3)->create();

        $response = $this->withToken($token)->get('/api/v1/reports/tasks/excel');

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('tasks_export_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_analytics_excel_export_returns_file(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withToken($token)->get('/api/v1/reports/analytics/excel');

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('analytics_summary_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_compliance_pdf_report_returns_pdf_file(): void
    {
        $admin = User::factory()->create();
        $token = $admin->createToken('admin-token')->plainTextToken;

        Task::factory()->count(2)->create();

        $response = $this->withToken($token)->get('/api/v1/reports/compliance/pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('workflow_compliance_report_', $disposition);
        $this->assertNotEmpty($response->getContent());
    }
}
