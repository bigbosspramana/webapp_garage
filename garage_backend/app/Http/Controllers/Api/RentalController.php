<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Sewa;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Class RentalController
 * 
 * Mengelola proses sewa garasi baru oleh Penyewa:
 * - Layar 5: Sewa Garasi Baru (1/2) - Pemilihan kendaraan, tipe garasi, tipe sewa, durasi, dan pengisian listrik EV.
 * - Layar 6: Sewa Garasi Baru (2/2) - Pemilihan slot garasi tersedia, kalkulasi rincian biaya, dan pembayaran / upload bukti pelunasan.
 */
class RentalController extends Controller
{
    /**
     * Menampilkan riwayat sewa dan daftar invoice milik pengguna (Layar 7).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $rentals = Sewa::with(['vehicle', 'garage', 'invoices' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Pisahkan menjadi sewa aktif dan riwayat selesai
        $activeRentals = $rentals->where('status', 'aktif')->values();
        $historyRentals = $rentals->where('status', '!=', 'aktif')->values();

        // Ambil seluruh invoice milik user
        $invoices = Invoice::whereHas('sewa', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['sewa.vehicle', 'sewa.garage'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'all' => $rentals,
                'active' => $activeRentals,
                'history' => $historyRentals,
                'invoices' => $invoices,
            ],
        ], 200);
    }

    /**
     * Mendapatkan daftar slot garasi (Layar 6 & Layar 8).
     * Jika parameter all=1, mengembalikan seluruh garasi dengan statusnya.
     * Jika tidak, hanya mengembalikan garasi berstatus available.
     */
    public function getAvailableGarages(Request $request): JsonResponse
    {
        $query = Garage::query();

        if (!$request->boolean('all')) {
            $query->where('status', 'available');
        }

        if ($request->has('type') && in_array($request->type, ['sepeda', 'mobil', 'mobil_listrik'])) {
            $query->where('type', $request->type);
        }

        $garages = $query->orderBy('code', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'count' => $garages->count(),
            'data' => $garages,
        ], 200);
    }

    /**
     * Membuat transaksi sewa garasi baru dan menerbitkan invoice pertama (Layar 6).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'garage_id' => 'required|exists:garages,id',
            'rental_type' => 'required|in:harian,bulanan',
            'total_days' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'with_charging' => 'nullable|boolean',
            'battery_input_mode' => 'nullable|in:persen,kwh',
            'battery_remaining_value' => 'nullable|numeric|min:0',
            'kwh_charged' => 'nullable|numeric|min:0',
            'payment_proof' => 'required', // File upload atau path bukti bayar
        ]);

        // Pastikan kendaraan milik user
        $vehicle = Vehicle::where('id', $request->vehicle_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kendaraan tidak ditemukan atau bukan milik Anda.',
            ], 403);
        }

        // Pastikan garasi tersedia
        $garage = Garage::where('id', $request->garage_id)
            ->where('status', 'available')
            ->first();

        if (!$garage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Slot garasi yang dipilih sudah tidak tersedia.',
            ], 422);
        }

        // Perhitungan Tanggal Selesai
        $startDate = Carbon::parse($request->start_date);
        $totalDays = (int) $request->total_days;
        $endDate = $startDate->copy()->addDays($totalDays - 1);

        // Kalkulasi Biaya sesuai Dokumen Spesifikasi UI/UX:
        // Catatan 2: Harian = jumlah hari × harga harian. Bulanan = 25 × harga harian.
        if ($request->rental_type === 'bulanan') {
            $rentalCost = 25 * (float) $garage->daily_price;
        } else {
            $rentalCost = $totalDays * (float) $garage->daily_price;
        }

        // Catatan 3: Biaya listrik (jika ada) = kWh diisi × harga per kWh
        $withCharging = (bool) $request->with_charging;
        $kwhCharged = $withCharging ? (float) ($request->kwh_charged ?? 0) : 0;
        $electricityPricePerKwh = (float) ($garage->electricity_price_per_kwh ?? 0);
        $electricityCost = $withCharging ? ($kwhCharged * $electricityPricePerKwh) : 0;

        $totalAmount = $rentalCost + $electricityCost;

        // Proses penyimpanan bukti pembayaran
        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = 'proof_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('payment_proofs', $filename, 'public');
            $paymentProofPath = 'payment_proofs/' . $filename;
        } elseif (is_string($request->payment_proof)) {
            $paymentProofPath = $request->payment_proof;
        }

        return DB::transaction(function () use (
            $user,
            $vehicle,
            $garage,
            $request,
            $startDate,
            $endDate,
            $totalDays,
            $withCharging,
            $kwhCharged,
            $rentalCost,
            $electricityCost,
            $totalAmount,
            $paymentProofPath
        ) {
            // 1. Simpan Transaksi Sewa
            $sewa = Sewa::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => $request->rental_type,
                'total_days' => $totalDays,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'with_charging' => $withCharging,
                'battery_input_mode' => $withCharging ? $request->battery_input_mode : null,
                'battery_remaining_value' => $withCharging ? $request->battery_remaining_value : null,
                'kwh_charged' => $withCharging ? $kwhCharged : null,
                'status' => 'aktif',
            ]);

            // 2. Ubah status garasi menjadi 'used' (Layar 6 Catatan 5)
            $garage->update(['status' => 'used']);

            // 3. Terbitkan Invoice pertama (Layar 6 Catatan 5)
            $invoiceCount = Invoice::count() + 1;
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($invoiceCount, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'sewa_id' => $sewa->id,
                'invoice_number' => $invoiceNumber,
                'type' => 'sewa_baru',
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'days' => $totalDays,
                'rental_cost' => $rentalCost,
                'electricity_cost' => $electricityCost,
                'total_amount' => $totalAmount,
                'status' => 'paid',
                'payment_proof_path' => $paymentProofPath,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Sewa garasi berhasil dibuat dan invoice pertama telah terbit.',
                'data' => [
                    'sewa' => $sewa->load(['vehicle', 'garage']),
                    'invoice' => $invoice,
                ],
            ], 201);
        });
    }
}
