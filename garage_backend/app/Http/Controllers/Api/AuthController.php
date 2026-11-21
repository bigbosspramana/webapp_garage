<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Class AuthController
 * 
 * Controller untuk menangani alur Autentikasi (Login, Register Penyewa, Logout, Profile).
 */
class AuthController extends Controller
{
    /**
     * Menangani proses login untuk Admin dan Penyewa (Single Endpoint).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email (username) wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Validasi keberadaan user dan kecocokan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 401);
        }

        // Jika user adalah ADMIN
        if ($user->role === 'admin') {
            $token = $user->createToken('auth_token_admin')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil sebagai Administrator.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'verification_status' => $user->verification_status,
                ],
                'redirect' => '/admin/dashboard',
            ], 200);
        }

        // Jika user adalah PENYEWA, cek status verifikasi akun
        if ($user->verification_status === 'pending') {
            return response()->json([
                'status' => 'pending',
                'message' => 'Akun Anda masih dalam proses verifikasi manual oleh Admin.',
            ], 403);
        }

        if ($user->verification_status === 'rejected') {
            return response()->json([
                'status' => 'rejected',
                'message' => 'Pendaftaran akun Anda ditolak oleh Admin.',
            ], 403);
        }

        // Status approved
        $token = $user->createToken('auth_token_penyewa')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil. Selamat datang kembali!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'verification_status' => $user->verification_status,
            ],
            'redirect' => '/dashboard',
        ], 200);
    }

    /**
     * Menangani pendaftaran akun baru untuk Penyewa.
     * Sesuai aturan: Status akun pending & TIDAK langsung auto-login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'required|digits:16|unique:users,nik',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'nik.required' => 'NIK wajib diisi tepat 16 digit angka.',
            'nik.digits' => 'NIK harus berupa tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar dalam sistem.',
            'name.required' => 'Nama lengkap wajib diisi sesuai KTP.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'penyewa',
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi KTP oleh Admin.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verification_status' => $user->verification_status,
            ],
        ], 201);
    }

    /**
     * Mengambil data profil user yang sedang login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user(),
        ]);
    }

    /**
     * Menangani proses logout dan mencabut token API aktif.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }
}
