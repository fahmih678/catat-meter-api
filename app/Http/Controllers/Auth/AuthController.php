<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user has superadmin role
            if (!$user->hasRole('superadmin')) {
                Log::warning('Unauthorized login attempt - User ID: ' . $user->id . ', Email: ' . $user->email);

                // Logout user immediately
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akses ditolak. Hanya pengguna dengan role Super Admin yang dapat mengakses sistem ini.',
                    'role_required' => 'Anda tidak memiliki izin akses yang cukup. Hubungi administrator sistem.',
                ])->withInput();
            }

            $request->session()->regenerate();

            // Log successful login
            Log::info('Superadmin login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('/dashboard');
        }

        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
            'superadmin_only' => 'Sistem ini hanya dapat diakses oleh Super Administrator.',
        ])->withInput();
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            Log::info('User logout', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
