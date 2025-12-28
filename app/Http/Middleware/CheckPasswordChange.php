<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Check if current route is NOT the profile or password update route to avoid loop
            $currentRoute = Route::currentRouteName();

            // Allow profile page and password update, and logout
            if (
                !in_array($currentRoute, [
                    'profile',
                    'profile.update',
                    'password.update',
                    'logout',
                    'sanctum.csrf-cookie',
                    'translations', // Allow translations to load
                    'initial-locale' // Allow locale to load
                ])
            ) {
                return redirect()->route('profile')->with('warning', __('Please change your password to continue.'));
            }
        }

        return $next($request);
    }
}
