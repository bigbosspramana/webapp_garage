<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Invoice
 * 
 * Model representasi Faktur/Tagihan Transaksi Sewa.
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sewa_id',
        'invoice_number',
        'type',
        'period_start',
        'period_end',
        'days',
        'rental_cost',
        'electricity_cost',
        'total_amount',
        'status',
        'payment_proof_path',
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
            'period_start' => 'date',
            'period_end' => 'date',
            'rental_cost' => 'decimal:2',
            'electricity_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke data Sewa induk.
     */
    public function sewa(): BelongsTo
    {
        return $this->belongsTo(Sewa::class);
    }

    /**
     * Relasi ke Admin yang membuat/memverifikasi tagihan ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
