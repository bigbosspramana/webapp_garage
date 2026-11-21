<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageManagementTest extends TestCase
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
    }

    public function test_admin_can_batch_create_garages(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admin/garages/batch', [
            'type' => 'mobil_listrik',
            'count' => 10,
            'daily_price' => 50000,
            'electricity_price_per_kwh' => 2500,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'created_count' => 10,
                'first_code' => 'E001',
                'last_code' => 'E010',
            ]);

        $this->assertDatabaseCount('garages', 10);
        $this->assertDatabaseHas('garages', [
            'code' => 'E001',
            'type' => 'mobil_listrik',
            'daily_price' => 50000,
            'electricity_price_per_kwh' => 2500,
            'status' => 'available',
        ]);
    }

    public function test_batch_create_generates_sequential_codes(): void
    {
        // First batch
        $this->actingAs($this->admin)->postJson('/api/admin/garages/batch', [
            'type' => 'mobil',
            'count' => 5,
            'daily_price' => 30000,
        ]);

        // Second batch
        $response = $this->actingAs($this->admin)->postJson('/api/admin/garages/batch', [
            'type' => 'mobil',
            'count' => 5,
            'daily_price' => 30000,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'first_code' => 'C006',
                'last_code' => 'C010',
            ]);
    }

    public function test_admin_can_get_garage_stats(): void
    {
        Garage::factory()->create([
            'code' => 'S001',
            'type' => 'sepeda',
            'daily_price' => 10000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/garages');

        $response->assertStatus(200)
            ->assertJsonPath('stats.sepeda.count', 1)
            ->assertJsonPath('stats.sepeda.remaining', 998)
            ->assertJsonPath('stats.sepeda.next_code', 'S002');
    }

    public function test_cannot_delete_used_garage(): void
    {
        $garage = Garage::factory()->create([
            'code' => 'C001',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'used',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/garages/{$garage->id}");

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('garages', ['id' => $garage->id]);
    }

    public function test_can_delete_available_garage(): void
    {
        $garage = Garage::factory()->create([
            'code' => 'C001',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/garages/{$garage->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('garages', ['id' => $garage->id]);
    }
}
