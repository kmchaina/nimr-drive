<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $sessionUser = session('user');
        
        if (!$sessionUser) {
            return redirect('/login');
        }

        $user = User::find($sessionUser['id']);
        
        if (!$user || !$user->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }
            return redirect('/dashboard')->with('error', 'You do not have admin access.');
        }

        return $next($request);
    }
}
