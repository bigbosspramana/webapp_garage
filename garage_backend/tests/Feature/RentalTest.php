<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Sewa;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Vehicle $evVehicle;
    protected Vehicle $motorVehicle;
    protected Garage $evGarage;
    protected Garage $motorGarage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'penyewa',
            'verification_status' => 'approved',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'verification_status' => 'approved',
        ]);

        $this->evVehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'type' => 'mobil_listrik',
            'plate_number' => 'DD 5678 CD',
            'brand' => 'Hyundai',
            'model' => 'Ioniq 5',
            'battery_capacity_kwh' => 77.0,
        ]);

        $this->motorVehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'type' => 'sepeda_motor',
            'plate_number' => 'DD 1234 AB',
            'brand' => 'Honda',
            'model' => 'Beat',
        ]);

        $this->evGarage = Garage::create([
            'code' => 'E001',
            'type' => 'mobil_listrik',
            'daily_price' => 50000.00,
            'electricity_price_per_kwh' => 2500.00,
            'status' => 'available',
            'created_by' => $this->admin->id,
        ]);

        $this->motorGarage = Garage::create([
            'code' => 'S001',
            'type' => 'sepeda',
            'daily_price' => 10000.00,
            'status' => 'available',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_can_fetch_available_garages(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/garages/available?type=mobil_listrik');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'E001');
    }

    public function test_can_fetch_user_rentals_and_invoices(): void
    {
        // Setup existing sewa & invoice
        $sewa = Sewa::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->evVehicle->id,
            'garage_id' => $this->evGarage->id,
            'rental_type' => 'harian',
            'total_days' => 7,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => 'aktif',
        ]);

        Invoice::create([
            'sewa_id' => $sewa->id,
            'invoice_number' => 'INV-20261122-0001',
            'type' => 'sewa_baru',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addDays(6)->toDateString(),
            'days' => 7,
            'rental_cost' => 350000.00,
            'electricity_cost' => 0.00,
            'total_amount' => 350000.00,
            'status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/rentals');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.active')
            ->assertJsonCount(1, 'data.invoices')
            ->assertJsonPath('data.active.0.garage.code', 'E001')
            ->assertJsonPath('data.invoices.0.invoice_number', 'INV-20261122-0001');
    }

    public function test_can_create_harian_rental_with_ev_charging(): void
    {
        // 7 hari rental, 20% sisa baterai -> butuh 61.6 kWh
        // rental_cost = 7 * 50.000 = 350.000
        // electricity_cost = 61.6 * 2.500 = 154.000
        // total = 504.000
        $response = $this->actingAs($this->user)->postJson('/api/rentals', [
            'vehicle_id' => $this->evVehicle->id,
            'garage_id' => $this->evGarage->id,
            'rental_type' => 'harian',
            'total_days' => 7,
            'start_date' => now()->toDateString(),
            'with_charging' => true,
            'battery_input_mode' => 'persen',
            'battery_remaining_value' => 20,
            'kwh_charged' => 61.6,
            'payment_proof' => 'dummy_proof.jpg',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sewa', [
            'user_id' => $this->user->id,
            'vehicle_id' => $this->evVehicle->id,
            'garage_id' => $this->evGarage->id,
            'rental_type' => 'harian',
            'total_days' => 7,
            'status' => 'aktif',
        ]);

        // Garasi status menjadi used
        $this->assertDatabaseHas('garages', [
            'id' => $this->evGarage->id,
            'status' => 'used',
        ]);

        // Invoice pertama terbit
        $this->assertDatabaseHas('invoices', [
            'type' => 'sewa_baru',
            'rental_cost' => 350000.00,
            'electricity_cost' => 154000.00,
            'total_amount' => 504000.00,
            'status' => 'paid',
        ]);
    }

    public function test_can_create_bulanan_rental_with_25x_daily_rate(): void
    {
        // Bulanan rental: 25 * 10.000 = 250.000
        $response = $this->actingAs($this->user)->postJson('/api/rentals', [
            'vehicle_id' => $this->motorVehicle->id,
            'garage_id' => $this->motorGarage->id,
            'rental_type' => 'bulanan',
            'total_days' => 30,
            'start_date' => now()->toDateString(),
            'with_charging' => false,
            'payment_proof' => 'dummy_proof_motor.jpg',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('invoices', [
            'type' => 'sewa_baru',
            'rental_cost' => 250000.00,
            'electricity_cost' => 0.00,
            'total_amount' => 250000.00,
        ]);
    }
}
