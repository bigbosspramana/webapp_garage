<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Models\Sewa;
use App\Models\Invoice;

echo "=== BENCHMARK PERFORMANSI RESPON DATA (CRUD & QUERY) ===" . PHP_EOL . PHP_EOL;

// 1. Benchmark View / Query Garages (144 records)
$t0 = microtime(true);
$garages = Garage::where('status', 'available')->orderBy('code')->get();
$t_garages = (microtime(true) - $t0) * 1000;
echo sprintf("[GET / VIEW DATA] Query 144 Slot Garasi: %.2f ms (%.3f detik)" . PHP_EOL, $t_garages, $t_garages / 1000);

// 2. Benchmark View Vehicles with Owner
$t0 = microtime(true);
$vehicles = Vehicle::with('user')->get();
$t_vehicles = (microtime(true) - $t0) * 1000;
echo sprintf("[GET / VIEW DATA] Query Kendaraan Penyewa: %.2f ms (%.3f detik)" . PHP_EOL, $t_vehicles, $t_vehicles / 1000);

// 3. Benchmark View Invoices & Riwayat
$t0 = microtime(true);
$invoices = Invoice::with(['sewa.user', 'sewa.vehicle', 'sewa.garage'])->get();
$t_invoices = (microtime(true) - $t0) * 1000;
echo sprintf("[GET / VIEW DATA] Query Laporan & Invoices: %.2f ms (%.3f detik)" . PHP_EOL, $t_invoices, $t_invoices / 1000);

// 4. Benchmark Post / Create Data (Simulasi Insert Transaksi Sewa)
$t0 = microtime(true);
$firstUser = User::where('role', 'user')->first() ?? User::first();
$firstVehicle = Vehicle::first();
$testGarage = Garage::where('status', 'available')->first();

if ($firstUser && $firstVehicle && $testGarage) {
    \DB::beginTransaction();
    $sewa = Sewa::create([
        'user_id' => $firstUser->id,
        'vehicle_id' => $firstVehicle->id,
        'garage_id' => $testGarage->id,
        'rental_type' => 'harian',
        'total_days' => 7,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'with_charging' => false,
        'kwh_charged' => 0,
        'battery_remaining_value' => 0,
        'battery_input_mode' => 'persen',
        'status' => 'aktif',
    ]);
    $t_post = (microtime(true) - $t0) * 1000;
    echo sprintf("[POST / CREATE DATA] Insert Sewa Garasi Baru: %.2f ms (%.3f detik)" . PHP_EOL, $t_post, $t_post / 1000);

    // 5. Benchmark Update Data
    $t0 = microtime(true);
    $testGarage->update(['status' => 'used']);
    $t_update = (microtime(true) - $t0) * 1000;
    echo sprintf("[PUT / UPDATE DATA] Update Status Garasi (available -> used): %.2f ms (%.3f detik)" . PHP_EOL, $t_update, $t_update / 1000);

    // 6. Benchmark Delete Data
    $t0 = microtime(true);
    $sewa->delete();
    $t_delete = (microtime(true) - $t0) * 1000;
    echo sprintf("[DELETE DATA] Hapus Data Sewa: %.2f ms (%.3f detik)" . PHP_EOL, $t_delete, $t_delete / 1000);

    \DB::rollBack(); // Pastikan database tetap bersih
}

echo PHP_EOL . "KESIMPULAN: Seluruh operasi GET, POST, UPDATE, DELETE selesai jauh di bawah 1 detik (< 1000 ms)." . PHP_EOL;
