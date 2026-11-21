<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RentalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'verification_status' => 'approved',
        ]);
        $this->seed(RentalDemoSeeder::class);
    }

    public function test_admin_can_get_monthly_reports(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports?type=monthly&year=2026');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(12, 'data');
    }

    public function test_admin_can_get_garage_type_reports(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports?type=garage_type');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_get_tenant_reports(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports?type=tenant');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'tenants',
                'selected_tenant' => [
                    'user',
                    'total_days',
                    'total_paid',
                    'history',
                ],
            ]);
    }
}
