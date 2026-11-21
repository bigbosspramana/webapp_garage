<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected User $penyewa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penyewa = User::factory()->create([
            'role' => 'penyewa',
            'verification_status' => 'approved',
        ]);
    }

    public function test_penyewa_can_get_their_vehicles(): void
    {
        Vehicle::create([
            'user_id' => $this->penyewa->id,
            'type' => 'sepeda_motor',
            'brand' => 'Honda',
            'model' => 'Beat',
            'plate_number' => 'DD 1234 AB',
        ]);

        $response = $this->actingAs($this->penyewa)->getJson('/api/vehicles');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model', 'Beat');
    }

    public function test_penyewa_can_add_car(): void
    {
        $response = $this->actingAs($this->penyewa)->postJson('/api/vehicles', [
            'type' => 'mobil',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'plate_number' => 'B 1234 XYZ',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.plate_number', 'B 1234 XYZ');

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $this->penyewa->id,
            'model' => 'Avanza',
        ]);
    }

    public function test_penyewa_can_add_ev_with_battery(): void
    {
        $response = $this->actingAs($this->penyewa)->postJson('/api/vehicles', [
            'type' => 'mobil_listrik',
            'brand' => 'Hyundai',
            'model' => 'Ioniq 5',
            'plate_number' => 'DD 5678 CD',
            'battery_capacity_kwh' => 77,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.battery_capacity_kwh', '77.00');
    }

    public function test_penyewa_can_add_bicycle_without_plate(): void
    {
        $response = $this->actingAs($this->penyewa)->postJson('/api/vehicles', [
            'type' => 'sepeda',
            'brand' => 'Polygon',
            'model' => 'Xtrada',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.plate_number', null);
    }
}
