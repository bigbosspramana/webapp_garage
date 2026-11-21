<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class AdminController
 * 
 * Controller untuk menangani operasional Admin (Verifikasi KTP, Manajemen Garasi, dsb).
 */
class AdminController extends Controller
{
    /**
     * Mengambil daftar pendaftar penyewa yang masih berstatus pending.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingUsers(Request $request): JsonResponse
    {
        // Pastikan hanya admin yang bisa mengakses
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya Administrator yang dapat melihat halaman ini.',
            ], 403);
        }

        $pendingUsers = User::where('role', 'penyewa')
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'nik', 'name', 'email', 'phone', 'created_at']);

        $formatted = $pendingUsers->map(function ($u) {
            // Format NIK: tampilkan sebagian seperti di spesifikasi (mis. 3273 •••• 0001)
            $maskedNik = $u->nik;
            if (strlen($u->nik) === 16) {
                $maskedNik = substr($u->nik, 0, 4) . ' •••• ' . substr($u->nik, -4);
            }

            return [
                'id' => $u->id,
                'nik_raw' => $u->nik,
                'nik' => $maskedNik,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'registered_date' => $u->created_at->translatedFormat('d M Y'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $formatted->count(),
            'data' => $formatted,
        ], 200);
    }

    /**
     * Menerima pendaftaran penyewa (status -> approved).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approveUser(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $user = User::where('id', $id)->where('role', 'penyewa')->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pendaftar tidak ditemukan.',
            ], 404);
        }

        $user->update([
            'verification_status' => 'approved',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pendaftaran atas nama {$user->name} berhasil diterima (Approved). Penyewa kini dapat login.",
            'user' => [
                'id' => $user->id,
                'verification_status' => $user->verification_status,
            ],
        ], 200);
    }

    /**
     * Menolak pendaftaran penyewa (status -> rejected).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectUser(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $user = User::where('id', $id)->where('role', 'penyewa')->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pendaftar tidak ditemukan.',
            ], 404);
        }

        $user->update([
            'verification_status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pendaftaran atas nama {$user->name} telah ditolak (Rejected).",
            'user' => [
                'id' => $user->id,
                'verification_status' => $user->verification_status,
            ],
        ], 200);
    }

    /**
     * Mengambil daftar garasi beserta ringkasan kuota per tipe.
     */
    public function getGarages(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $type = $request->query('type');
        $query = Garage::query()->orderBy('code', 'asc');
        if ($type) {
            $query->where('type', $type);
        }
        $garages = $query->get();

        // Hitung kuota per tipe
        $types = ['sepeda', 'mobil', 'mobil_listrik'];
        $prefixes = ['sepeda' => 'S', 'mobil' => 'C', 'mobil_listrik' => 'E'];
        $stats = [];

        foreach ($types as $t) {
            $count = Garage::where('type', $t)->count();
            $maxNum = 0;
            $existingCodes = Garage::where('type', $t)->pluck('code');
            foreach ($existingCodes as $c) {
                $num = intval(substr($c, 1));
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }

            $stats[$t] = [
                'count' => $count,
                'max_quota' => 999,
                'remaining' => max(0, 999 - $count),
                'next_num' => $maxNum + 1,
                'next_code' => $prefixes[$t] . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT),
            ];
        }

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'data' => $garages,
        ], 200);
    }

    /**
     * Membuat garasi berkelompok (Batch Create) dengan kode urut otomatis (S001, C001, E001).
     */
    public function batchCreateGarages(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'type' => 'required|in:sepeda,mobil,mobil_listrik',
            'count' => 'required|integer|min:1|max:999',
            'daily_price' => 'required|numeric|min:0',
            'electricity_price_per_kwh' => 'nullable|numeric|min:0',
        ]);

        $type = $request->type;
        $count = (int) $request->count;
        $dailyPrice = $request->daily_price;
        $electricityPrice = $type === 'mobil_listrik' ? ($request->electricity_price_per_kwh ?? 0) : null;

        $prefixMap = [
            'sepeda' => 'S',
            'mobil' => 'C',
            'mobil_listrik' => 'E',
        ];
        $prefix = $prefixMap[$type];

        return DB::transaction(function () use ($request, $type, $count, $dailyPrice, $electricityPrice, $prefix) {
            $currentCount = Garage::where('type', $type)->count();
            if ($currentCount + $count > 999) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Kuota melebihi batas maksimal. Sisa slot yang dapat dibuat: " . (999 - $currentCount) . " slot.",
                ], 422);
            }

            // Tentukan nomor awal berdasarkan kode terbesar yang sudah ada
            $existingCodes = Garage::where('type', $type)->pluck('code');
            $maxNum = 0;
            foreach ($existingCodes as $c) {
                $num = intval(substr($c, 1));
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }

            $createdGarages = [];
            $now = now();

            for ($i = 1; $i <= $count; $i++) {
                $seq = $maxNum + $i;
                $code = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

                $createdGarages[] = [
                    'code' => $code,
                    'type' => $type,
                    'daily_price' => $dailyPrice,
                    'electricity_price_per_kwh' => $electricityPrice,
                    'status' => 'available',
                    'created_by' => $request->user()->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Garage::insert($createdGarages);

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil membuat {$count} slot garasi tipe {$type}.",
                'created_count' => $count,
                'first_code' => $createdGarages[0]['code'],
                'last_code' => $createdGarages[$count - 1]['code'],
            ], 201);
        });
    }

    /**
     * Memperbarui tarif/harga garasi.
     */
    public function updateGarage(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $garage = Garage::find($id);
        if (!$garage) {
            return response()->json(['status' => 'error', 'message' => 'Garasi tidak ditemukan.'], 404);
        }

        $request->validate([
            'daily_price' => 'required|numeric|min:0',
            'electricity_price_per_kwh' => 'nullable|numeric|min:0',
        ]);

        $garage->daily_price = $request->daily_price;
        if ($garage->type === 'mobil_listrik') {
            $garage->electricity_price_per_kwh = $request->electricity_price_per_kwh ?? 0;
        }
        $garage->save();

        return response()->json([
            'status' => 'success',
            'message' => "Garasi {$garage->code} berhasil diperbarui.",
            'data' => $garage,
        ], 200);
    }

    /**
     * Menghapus garasi (hanya jika berstatus available / tidak sedang disewa).
     */
    public function deleteGarage(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $garage = Garage::find($id);
        if (!$garage) {
            return response()->json(['status' => 'error', 'message' => 'Garasi tidak ditemukan.'], 404);
        }

        if ($garage->status === 'used') {
            return response()->json([
                'status' => 'error',
                'message' => "Garasi {$garage->code} sedang aktif digunakan penyewa dan tidak dapat dihapus.",
            ], 422);
        }

        $code = $garage->code;
        $garage->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Garasi {$code} berhasil dihapus.",
        ], 200);
    }
}
