<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Garage
 * 
 * Model representasi entitas Slot Garasi.
 */
class Garage extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'type',
        'daily_price',
        'electricity_price_per_kwh',
        'status',
        'created_by',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_price' => 'decimal:2',
            'electricity_price_per_kwh' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Admin pembuat data garasi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke riwayat sewa pada garasi ini.
     */
    public function sewa(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }
}
