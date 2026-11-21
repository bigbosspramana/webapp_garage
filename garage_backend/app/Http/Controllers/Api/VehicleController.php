<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class VehicleController
 * 
 * Mengelola kendaraan milik Penyewa (Kendaraan Saya & Form Tambah Kendaraan).
 */
class VehicleController extends Controller
{
    /**
     * Menampilkan daftar kendaraan milik pengguna yang sedang login (Layar 3).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $vehicles = Vehicle::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $vehicles->count(),
            'data' => $vehicles,
        ], 200);
    }

    /**
     * Menyimpan kendaraan baru milik pengguna (Layar 4).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $type = $request->type;

        // Aturan validasi:
        // Catatan 1: Tipe: Sepeda, Sepeda Motor, Mobil, Mobil Listrik
        // Catatan 2: Nomor plat wajib; bila Sepeda otomatis opsional/null
        // Catatan 3: Kapasitas baterai (kWh) hanya muncul & wajib untuk Mobil Listrik
        $request->validate([
            'type' => 'required|in:sepeda,sepeda_motor,mobil,mobil_listrik',
            'plate_number' => $type === 'sepeda' ? 'nullable|string|max:20' : 'required|string|max:20',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'battery_capacity_kwh' => $type === 'mobil_listrik' ? 'required|numeric|min:0.1' : 'nullable|numeric',
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'type' => $type,
            'plate_number' => $type === 'sepeda' ? null : strtoupper(trim($request->plate_number)),
            'brand' => trim($request->brand),
            'model' => trim($request->model),
            'battery_capacity_kwh' => $type === 'mobil_listrik' ? $request->battery_capacity_kwh : null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Kendaraan {$vehicle->brand} {$vehicle->model} berhasil ditambahkan.",
            'data' => $vehicle,
        ], 201);
    }

    /**
     * Menghapus kendaraan pengguna (jika tidak sedang disewa aktif).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak ditemukan.'], 404);
        }

        // Cek apakah sedang digunakan dalam sewa aktif
        $hasActiveRental = $vehicle->sewa()->where('status', 'aktif')->exists();
        if ($hasActiveRental) {
            return response()->json([
                'status' => 'error',
                'message' => "Kendaraan {$vehicle->brand} {$vehicle->model} sedang dalam masa sewa garasi aktif dan tidak dapat dihapus.",
            ], 422);
        }

        $desc = "{$vehicle->brand} {$vehicle->model}";
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Kendaraan {$desc} berhasil dihapus.",
        ], 200);
    }
}
