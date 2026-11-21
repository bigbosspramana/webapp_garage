<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Sewa
 * 
 * Model representasi transaksi Penyewaan Garasi.
 */
class Sewa extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'sewa';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'garage_id',
        'rental_type',
        'total_days',
        'start_date',
        'end_date',
        'with_charging',
        'battery_input_mode',
        'battery_remaining_value',
        'kwh_charged',
        'status',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'with_charging' => 'boolean',
            'battery_remaining_value' => 'decimal:2',
            'kwh_charged' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Penyewa (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Kendaraan yang diparkir.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Relasi ke Garasi yang disewa.
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Relasi ke tagihan/invoice transaksi ini.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
