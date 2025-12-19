<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LdapAuthService;

class UserIsolationMiddleware
{
    protected $ldapAuthService;

    public function __construct(LdapAuthService $ldapAuthService)
    {
        $this->ldapAuthService = $ldapAuthService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        $user = session('user');
        if (!$user) {
            return redirect('/login');
        }

        // For file operations, validate path access
        if ($request->has('path')) {
            $requestedPath = $request->input('path') ?? '';
            
            // Get user from database
            $userModel = \App\Models\User::find($user['id']);
            if (!$userModel) {
                abort(401, 'User not found');
            }
            
            // For file operations, empty path means user's root folder (allowed)
            if (empty($requestedPath)) {
                // Empty path is allowed for file operations (user's root folder)
                return $next($request);
            }
            
            if (!$this->ldapAuthService->hasPathAccess($userModel, $requestedPath)) {
                abort(403, 'Access denied to requested path');
            }
        }

        // Validate file upload paths
        if ($request->isMethod('POST') && $request->hasFile('files')) {
            $uploadPath = $request->input('upload_path') ?? '';
            
            // Get user from database
            $userModel = \App\Models\User::find($user['id']);
            if (!$userModel) {
                abort(401, 'User not found');
            }
            
            if (!$this->ldapAuthService->hasPathAccess($userModel, $uploadPath)) {
                abort(403, 'Access denied to upload path');
            }
        }

        return $next($request);
    }
}
