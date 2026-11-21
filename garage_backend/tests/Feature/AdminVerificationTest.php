<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pendingUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin 1',
            'email' => 'admin1@gvehicle.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'verification_status' => 'approved',
        ]);

        $this->pendingUser = User::create([
            'nik' => '3273123456780001',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'password' => Hash::make('password123'),
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);
    }

    public function test_admin_can_view_pending_users(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/pending-users');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'count' => 1,
            ]);
    }

    public function test_admin_can_approve_pending_user(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$this->pendingUser->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->pendingUser->id,
            'verification_status' => 'approved',
            'verified_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_pending_user(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/users/{$this->pendingUser->id}/reject");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->pendingUser->id,
            'verification_status' => 'rejected',
            'verified_by' => $this->admin->id,
        ]);
    }

    public function test_penyewa_cannot_access_admin_verification(): void
    {
        $response = $this->actingAs($this->pendingUser, 'sanctum')
            ->getJson('/api/admin/pending-users');

        $response->assertStatus(403);
    }
}
