<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Vehicle
 * 
 * Model representasi entitas Kendaraan milik Pengguna.
 */
class Vehicle extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'plate_number',
        'brand',
        'model',
        'battery_capacity_kwh',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'battery_capacity_kwh' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Pemilik Kendaraan (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke riwayat Sewa yang menggunakan kendaraan ini.
     */
    public function sewa(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }
}
