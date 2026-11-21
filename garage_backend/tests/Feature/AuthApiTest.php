<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed initial admin
        User::create([
            'name' => 'Administrator 1',
            'email' => 'admin1@gvehicle.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'verification_status' => 'approved',
        ]);
    }

    public function test_admin_can_login_successfully(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin1@gvehicle.com',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'email' => 'admin1@gvehicle.com',
                    'role' => 'admin',
                ],
            ]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin1@gvehicle.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Email atau password yang Anda masukkan salah.',
            ]);
    }

    public function test_penyewa_can_register_and_starts_as_pending(): void
    {
        $response = $this->postJson('/api/register', [
            'nik' => '1234567890123456',
            'name' => 'Budi Santoso',
            'phone' => '081299998888',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi KTP oleh Admin.',
                'user' => [
                    'verification_status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'nik' => '1234567890123456',
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);
    }

    public function test_pending_penyewa_login_is_rejected_with_pending_message(): void
    {
        User::create([
            'nik' => '3201010101010001',
            'name' => 'Pending User',
            'phone' => '081111222333',
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'pending',
                'message' => 'Akun Anda masih dalam proses verifikasi manual oleh Admin.',
            ]);
    }

    public function test_approved_penyewa_can_login(): void
    {
        User::create([
            'nik' => '3201010101010002',
            'name' => 'Approved User',
            'phone' => '081111222334',
            'email' => 'approved@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyewa',
            'verification_status' => 'approved',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'approved@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'email' => 'approved@example.com',
                    'role' => 'penyewa',
                ],
            ]);
        $this->assertNotEmpty($response->json('token'));
    }
}
