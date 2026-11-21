<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
