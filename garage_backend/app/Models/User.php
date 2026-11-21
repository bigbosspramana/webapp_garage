<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * 
 * Model representasi entitas Pengguna (Admin dan Penyewa).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nik',
        'phone',
        'email',
        'password',
        'role',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi JSON / array.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke entitas Kendaraan (1 Pengguna memiliki banyak Kendaraan).
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Relasi ke entitas Sewa (1 Pengguna memiliki banyak transaksi Sewa).
     */
    public function sewa(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }

    /**
     * Relasi ke Garasi yang ditambahkan oleh Admin ini.
     */
    public function garagesCreated(): HasMany
    {
        return $this->hasMany(Garage::class, 'created_by');
    }

    /**
     * Relasi ke Invoice yang dibuat/diverifikasi oleh Admin ini.
     */
    public function invoicesCreated(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * Relasi ke Pengguna yang diverifikasi oleh Admin ini.
     */
    public function verifiedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'verified_by');
    }

    /**
     * Relasi ke Admin yang memverifikasi akun ini.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
