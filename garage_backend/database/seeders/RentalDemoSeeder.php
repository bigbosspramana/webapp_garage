<?php

namespace Database\Seeders;

use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Sewa;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RentalDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Penyewa
        $budi = User::firstOrCreate(
            ['email' => 'budi@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'nik' => '3273010101900001',
                'phone' => '081234567890',
                'password' => Hash::make('password123'),
                'role' => 'penyewa',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ]
        );

        $sari = User::firstOrCreate(
            ['email' => 'sari@gmail.com'],
            [
                'name' => 'Sari Dewi',
                'nik' => '3273020202950002',
                'phone' => '081234567891',
                'password' => Hash::make('password123'),
                'role' => 'penyewa',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ]
        );

        $andi = User::firstOrCreate(
            ['email' => 'andi@gmail.com'],
            [
                'name' => 'Andi Pratama',
                'nik' => '3273030303980003',
                'phone' => '081234567892',
                'password' => Hash::make('password123'),
                'role' => 'penyewa',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ]
        );

        // 2. Data Kendaraan
        $vBudi = Vehicle::firstOrCreate(
            ['user_id' => $budi->id],
            [
                'type' => 'mobil_listrik',
                'brand' => 'Hyundai',
                'model' => 'Ioniq 5',
                'plate_number' => 'B 1001 EV',
                'battery_capacity_kwh' => 72.6,
            ]
        );

        $vSari = Vehicle::firstOrCreate(
            ['user_id' => $sari->id],
            [
                'type' => 'mobil',
                'brand' => 'Toyota',
                'model' => 'Avanza',
                'plate_number' => 'B 2002 TY',
            ]
        );

        $vAndi = Vehicle::firstOrCreate(
            ['user_id' => $andi->id],
            [
                'type' => 'sepeda_motor',
                'brand' => 'Honda',
                'model' => 'Beat',
                'plate_number' => 'B 3003 HB',
            ]
        );

        // 3. Data Garasi
        $gE002 = Garage::firstOrCreate(
            ['code' => 'E002'],
            [
                'type' => 'mobil_listrik',
                'daily_price' => 50000,
                'electricity_price_per_kwh' => 2500,
                'status' => 'used',
            ]
        );

        $gC005 = Garage::firstOrCreate(
            ['code' => 'C005'],
            [
                'type' => 'mobil',
                'daily_price' => 30000,
                'electricity_price_per_kwh' => null,
                'status' => 'used',
            ]
        );

        $gS011 = Garage::firstOrCreate(
            ['code' => 'S011'],
            [
                'type' => 'sepeda',
                'daily_price' => 10000,
                'electricity_price_per_kwh' => null,
                'status' => 'used',
            ]
        );

        // 4. Data Sewa Aktif
        $sewa1 = Sewa::firstOrCreate(
            ['garage_id' => $gE002->id, 'status' => 'aktif'],
            [
                'user_id' => $budi->id,
                'vehicle_id' => $vBudi->id,
                'rental_type' => 'harian',
                'total_days' => 7,
                'start_date' => Carbon::parse('2026-11-21'),
                'end_date' => Carbon::parse('2026-11-28'),
                'with_charging' => true,
                'battery_input_mode' => 'persen',
                'battery_remaining_value' => 20,
                'kwh_charged' => 58.08,
            ]
        );

        $sewa2 = Sewa::firstOrCreate(
            ['garage_id' => $gC005->id, 'status' => 'aktif'],
            [
                'user_id' => $sari->id,
                'vehicle_id' => $vSari->id,
                'rental_type' => 'harian',
                'total_days' => 4,
                'start_date' => Carbon::parse('2026-11-21'),
                'end_date' => Carbon::parse('2026-11-25'),
                'with_charging' => false,
            ]
        );

        $sewa3 = Sewa::firstOrCreate(
            ['garage_id' => $gS011->id, 'status' => 'aktif'],
            [
                'user_id' => $andi->id,
                'vehicle_id' => $vAndi->id,
                'rental_type' => 'harian',
                'total_days' => 3,
                'start_date' => Carbon::parse('2026-11-21'),
                'end_date' => Carbon::parse('2026-11-24'),
                'with_charging' => false,
            ]
        );

        // 5. Invoice Awal (Sewa Baru #1)
        Invoice::firstOrCreate(
            ['sewa_id' => $sewa1->id, 'type' => 'sewa_baru'],
            [
                'invoice_number' => 'INV-20261121-0001',
                'period_start' => Carbon::parse('2026-11-21'),
                'period_end' => Carbon::parse('2026-11-28'),
                'days' => 7,
                'rental_cost' => 350000,
                'electricity_cost' => 145200,
                'total_amount' => 495200,
                'status' => 'verified',
            ]
        );

        Invoice::firstOrCreate(
            ['sewa_id' => $sewa2->id, 'type' => 'sewa_baru'],
            [
                'invoice_number' => 'INV-20261121-0002',
                'period_start' => Carbon::parse('2026-11-21'),
                'period_end' => Carbon::parse('2026-11-25'),
                'days' => 4,
                'rental_cost' => 120000,
                'electricity_cost' => 0,
                'total_amount' => 120000,
                'status' => 'verified',
            ]
        );

        Invoice::firstOrCreate(
            ['sewa_id' => $sewa3->id, 'type' => 'sewa_baru'],
            [
                'invoice_number' => 'INV-20261121-0003',
                'period_start' => Carbon::parse('2026-11-21'),
                'period_end' => Carbon::parse('2026-11-24'),
                'days' => 3,
                'rental_cost' => 30000,
                'electricity_cost' => 0,
                'total_amount' => 30000,
                'status' => 'verified',
            ]
        );
    }
}
