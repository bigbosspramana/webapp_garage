<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Class AdminSeeder
 * 
 * Seeder untuk akun Administrator utama sesuai spesifikasi proyek.
 */
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin1@gvehicle.com'],
            [
                'name' => 'Administrator 1',
                'nik' => null,
                'phone' => '081234567890',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'verification_status' => 'approved',
                'email_verified_at' => now(),
                'verified_at' => now(),
            ]
        );
    }
}
