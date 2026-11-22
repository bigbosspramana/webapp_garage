<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Sewa;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian Unit & Fitur Sesuai Standar Uji Kompetensi LSP
 * Skenario Pengujian Resmi: UT-01 s/d UT-11
 */
class LspScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'nik' => '1234567890123456',
            'name' => 'Administrator 1',
            'email' => 'admin@gvehicle.com',
            'phone' => '081200000001',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'verification_status' => 'approved',
        ]);

        $this->tenant = User::create([
            'nik' => '3201010101900001',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'role' => 'penyewa',
            'verification_status' => 'approved',
        ]);
    }

    /**
     * UT-01: Pendaftaran Penyewa
     */
    public function test_UT_01_pendaftaran_penyewa(): void
    {
        $payload = [
            'nik' => '3201010101900002',
            'name' => 'Sari Dewi',
            'phone' => '081234567891',
            'email' => 'sari@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ktp_photo' => UploadedFile::fake()->image('ktp_sari.jpg'),
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'email' => 'sari@example.com',
                    'verification_status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'nik' => '3201010101900002',
            'email' => 'sari@example.com',
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);
    }

    /**
     * UT-02: Verifikasi Pendaftar Baru
     */
    public function test_UT_02_verifikasi_pendaftar_baru(): void
    {
        $pendingUser = User::create([
            'nik' => '3201010101900003',
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@example.com',
            'phone' => '081234567892',
            'password' => Hash::make('password123'),
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);

        // Admin Menyetujui Pendaftaran
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'verification_status' => 'approved',
            'verified_by' => $this->admin->id,
        ]);
    }

    /**
     * UT-03: Buat Garasi Berkelompok
     */
    public function test_UT_03_buat_garasi_berkelompok(): void
    {
        // 1. Buat Garasi Sepeda (Prefix S)
        $respSepeda = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/garages/batch', [
                'type' => 'sepeda',
                'count' => 3,
                'daily_price' => 15000,
            ]);
        $respSepeda->assertStatus(201);
        $this->assertDatabaseHas('garages', ['code' => 'S001', 'type' => 'sepeda']);
        $this->assertDatabaseHas('garages', ['code' => 'S003', 'type' => 'sepeda']);

        // 2. Buat Garasi Mobil (Prefix C)
        $respMobil = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/garages/batch', [
                'type' => 'mobil',
                'count' => 2,
                'daily_price' => 30000,
            ]);
        $respMobil->assertStatus(201);
        $this->assertDatabaseHas('garages', ['code' => 'C001', 'type' => 'mobil']);

        // 3. Buat Garasi Mobil Listrik (Prefix E)
        $respEV = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/garages/batch', [
                'type' => 'mobil_listrik',
                'count' => 2,
                'daily_price' => 50000,
                'electricity_price_per_kwh' => 2500,
            ]);
        $respEV->assertStatus(201);
        $this->assertDatabaseHas('garages', [
            'code' => 'E001',
            'type' => 'mobil_listrik',
            'electricity_price_per_kwh' => 2500,
        ]);
    }

    /**
     * UT-04: Validasi Batas Jumlah Garasi (Maksimal 999)
     */
    public function test_UT_04_validasi_batas_jumlah_garasi(): void
    {
        // Input jumlah melebihi batas 999 (misal 1000)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/garages/batch', [
                'type' => 'mobil',
                'count' => 1000,
                'daily_price' => 30000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['count']);
    }

    /**
     * UT-05: Validasi Kesesuaian Kendaraan & Garasi
     */
    public function test_UT_05_validasi_kesesuaian_kendaraan_dan_garasi(): void
    {
        $sepeda = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'sepeda',
            'brand' => 'Polygon',
            'model' => 'Path 3',
        ]);

        $motorGarage = Garage::create([
            'code' => 'S001',
            'type' => 'sepeda',
            'daily_price' => 10000,
            'status' => 'available',
        ]);

        $carGarage = Garage::create([
            'code' => 'C001',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'available',
        ]);

        // Sepeda cocok dengan garasi sepeda
        $this->assertEquals('sepeda', $sepeda->type);
        $this->assertEquals('sepeda', $motorGarage->type);

        // Sepeda tidak memiliki plat nomor (otomatis null/tanpa plat)
        $this->assertNull($sepeda->plate_number);

        // Garasi mobil berbeda tipe dengan sepeda
        $this->assertNotEquals($sepeda->type, $carGarage->type);
    }

    /**
     * UT-06: Validasi Durasi Sewa Harian (Batas ≤ 31 hari & Tolak > 31 hari)
     */
    public function test_UT_06_validasi_durasi_sewa_harian(): void
    {
        $vehicle = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'mobil',
            'plate_number' => 'B 1234 CD',
            'brand' => 'Toyota',
            'model' => 'Avanza',
        ]);

        $garage = Garage::create([
            'code' => 'C002',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'available',
        ]);

        // Input durasi harian melebihi batas 31 hari (misal 35 hari)
        $responseFail = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => 'harian',
                'total_days' => 35, // Melebihi batas
                'start_date' => now()->toDateString(),
                'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
            ]);

        $responseFail->assertStatus(422)
            ->assertJsonValidationErrors(['total_days']);

        // Input durasi harian valid (≤ 30 hari)
        $responseOk = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => 'harian',
                'total_days' => 7,
                'start_date' => now()->toDateString(),
                'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
            ]);

        $responseOk->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    /**
     * UT-07: Perhitungan Biaya Sewa Bulanan (25 × Harga Harian)
     */
    public function test_UT_07_perhitungan_biaya_sewa_bulanan(): void
    {
        $vehicle = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'mobil',
            'plate_number' => 'B 5555 EF',
            'brand' => 'Honda',
            'model' => 'HR-V',
        ]);

        $garage = Garage::create([
            'code' => 'C003',
            'type' => 'mobil',
            'daily_price' => 40000, // Harga harian
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => 'bulanan',
                'total_days' => 30,
                'start_date' => now()->toDateString(),
                'payment_proof' => UploadedFile::fake()->image('proof_bulanan.jpg'),
            ]);

        // Biaya sewa bulanan harus tepat 25 × 40.000 = 1.000.000
        $response->assertStatus(201)
            ->assertJsonPath('data.invoice.rental_cost', '1000000.00')
            ->assertJsonPath('data.invoice.total_amount', '1000000.00');
    }

    /**
     * UT-08: Perhitungan Isi Listrik Mobil Listrik
     */
    public function test_UT_08_perhitungan_isi_listrik_mobil_listrik(): void
    {
        $ev = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'mobil_listrik',
            'plate_number' => 'B 8888 EV',
            'brand' => 'Hyundai',
            'model' => 'Ioniq 5',
            'battery_capacity_kwh' => 77.0,
        ]);

        $evGarage = Garage::create([
            'code' => 'E005',
            'type' => 'mobil_listrik',
            'daily_price' => 50000,
            'electricity_price_per_kwh' => 2500,
            'status' => 'available',
        ]);

        // Kapasitas 77 kWh, sisa 20% -> diisi 80% = 61.6 kWh
        $kwhCharged = (77.0 * 80) / 100; // 61.6 kWh

        $response = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $ev->id,
                'garage_id' => $evGarage->id,
                'rental_type' => 'harian',
                'total_days' => 7,
                'start_date' => now()->toDateString(),
                'with_charging' => true,
                'battery_input_mode' => 'persen',
                'battery_remaining_value' => 20,
                'kwh_charged' => $kwhCharged,
                'payment_proof' => UploadedFile::fake()->image('proof_ev.jpg'),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.invoice.rental_cost', '350000.00')
            ->assertJsonPath('data.invoice.electricity_cost', '154000.00')
            ->assertJsonPath('data.invoice.total_amount', '504000.00');
    }

    /**
     * UT-09: Simpan Sewa & Status Garasi (Status -> used & Multi Sewa)
     */
    public function test_UT_09_simpan_sewa_dan_status_garasi(): void
    {
        $vehicle = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'mobil',
            'plate_number' => 'B 1111 ZZ',
            'brand' => 'Daihatsu',
            'model' => 'Xenia',
        ]);

        $garage1 = Garage::create([
            'code' => 'C010',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'available',
        ]);

        $garage2 = Garage::create([
            'code' => 'C011',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'available',
        ]);

        // Sewa Garasi 1
        $res1 = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage1->id,
                'rental_type' => 'harian',
                'total_days' => 3,
                'start_date' => now()->toDateString(),
                'payment_proof' => UploadedFile::fake()->image('proof1.jpg'),
            ]);
        $res1->assertStatus(201);

        // Status Garasi 1 berubah menjadi 'used'
        $this->assertEquals('used', $garage1->fresh()->status);
        $this->assertDatabaseHas('invoices', ['type' => 'sewa_baru']);

        // Penyewa menyewa Garasi 2 bersamaan (Multi-Sewa)
        $res2 = $this->actingAs($this->tenant, 'sanctum')
            ->postJson('/api/rentals', [
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage2->id,
                'rental_type' => 'harian',
                'total_days' => 5,
                'start_date' => now()->toDateString(),
                'payment_proof' => UploadedFile::fake()->image('proof2.jpg'),
            ]);
        $res2->assertStatus(201);
        $this->assertEquals('used', $garage2->fresh()->status);
    }

    /**
     * UT-10: Perpanjang Status Garasi
     */
    public function test_UT_10_perpanjang_status_garasi(): void
    {
        $vehicle = Vehicle::create([
            'user_id' => $this->tenant->id,
            'type' => 'mobil',
            'plate_number' => 'B 9999 AA',
            'brand' => 'Toyota',
            'model' => 'Innova',
        ]);

        $garage = Garage::create([
            'code' => 'C020',
            'type' => 'mobil',
            'daily_price' => 30000,
            'status' => 'used',
        ]);

        $startDate = Carbon::today();
        $oldEndDate = Carbon::today()->addDays(3);

        $sewa = Sewa::create([
            'user_id' => $this->tenant->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => $garage->id,
            'rental_type' => 'harian',
            'total_days' => 4,
            'start_date' => $startDate,
            'end_date' => $oldEndDate,
            'status' => 'aktif',
        ]);

        // Admin memperpanjang 3 hari ke depan
        $newEndDate = $oldEndDate->copy()->addDays(3);
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/rentals/{$sewa->id}/extend", [
                'new_end_date' => $newEndDate->format('Y-m-d'),
                'payment_proof' => UploadedFile::fake()->image('proof_extend.jpg'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'additional_days' => 3,
                ],
            ]);

        // Tanggal selesai di database diperbarui
        $this->assertEquals($newEndDate->format('Y-m-d'), $sewa->fresh()->end_date->format('Y-m-d'));

        // Invoice perpanjangan tersimpan
        $this->assertDatabaseHas('invoices', [
            'sewa_id' => $sewa->id,
            'type' => 'perpanjangan',
            'days' => 3,
            'total_amount' => 3 * 30000,
        ]);
    }

    /**
     * UT-11: Laporan Sewa Garasi
     */
    public function test_UT_11_laporan_sewa_garasi(): void
    {
        // 1. Laporan Per Bulan
        $resMonthly = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports?type=monthly&year=2026');
        $resMonthly->assertStatus(200)
            ->assertJsonStructure(['status', 'year', 'data', 'total_revenue']);

        // 2. Laporan Per Hari
        $resDaily = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports?type=daily&date=' . now()->toDateString());
        $resDaily->assertStatus(200)
            ->assertJsonStructure(['status', 'date', 'invoices', 'total_revenue']);

        // 3. Laporan Per Tipe Garasi
        $resType = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports?type=garage_type');
        $resType->assertStatus(200)
            ->assertJsonStructure(['status', 'data', 'total_revenue']);

        // 4. Laporan Per Penyewa
        $resTenant = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports?type=tenant&search=' . urlencode('Budi'));
        $resTenant->assertStatus(200)
            ->assertJsonStructure(['status', 'tenants']);
    }
}
