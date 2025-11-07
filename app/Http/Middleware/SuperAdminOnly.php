<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Check if user has superadmin role
        if (!Auth::user()->hasRole('superadmin')) {
            Log::warning('Unauthorized access attempt - User ID: ' . Auth::user()->id .
                       ', Route: ' . $request->path() .
                       ', IP: ' . $request->ip());

            // Force logout user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->withErrors([
                    'email' => 'Akses ditolak. Hanya Super Administrator yang dapat mengakses halaman ini.',
                    'unauthorized' => 'Anda tidak memiliki izin akses yang cukup. Hubungi administrator sistem.'
                ]);
        }

        return $next($request);
    }
}
