<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\LdapAuthService;

class AuthController extends Controller
{
    protected $ldapAuthService;

    public function __construct(LdapAuthService $ldapAuthService)
    {
        $this->ldapAuthService = $ldapAuthService;
    }

    public function showLogin()
    {
        // If user is already logged in, redirect to dashboard
        if (session('user')) {
            return redirect('/dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:1',
        ]);

        // Authenticate against Active Directory
        $user = $this->ldapAuthService->authenticate(
            $request->username,
            $request->password
        );

        if (!$user) {
            throw ValidationException::withMessages([
                'username' => 'The provided credentials are incorrect or the user account is not found in Active Directory.',
            ]);
        }

        // Create user session
        session([
            'user' => [
                'id' => $user->id,
                'username' => $user->ad_username,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'quota_bytes' => $user->quota_bytes,
                'used_bytes' => $user->used_bytes,
                'folder_path' => $user->folder_path,
                'last_login' => $user->last_login,
            ]
        ]);

        // Set session timeout (2 hours by default)
        config(['session.lifetime' => config('session.lifetime', 120)]);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        // Clear user session
        $request->session()->forget('user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Check if current session is valid
     */
    public function checkSession(Request $request)
    {
        $user = session('user');
        
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user' => $user,
        ]);
    }
}