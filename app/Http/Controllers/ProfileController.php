<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $sessionUser = session('user');

        if (!$sessionUser) {
            return redirect('/login');
        }

        $user = User::find($sessionUser['id']);
        if (!$user) {
            return redirect('/login');
        }

        return Inertia::render('Profile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'ad_username' => $user->ad_username,
                'last_login' => optional($user->last_login)?->toISOString(),
            ],
            'quota' => [
                'used_bytes' => $user->used_bytes,
                'total_bytes' => $user->quota_bytes,
                'usage_percentage' => $user->quota_usage_percentage,
            ],
            'appName' => config('app.name', 'NIMR Storage'),
        ]);
    }
}


