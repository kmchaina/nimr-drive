<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LdapAuthService;
use App\Services\SharingService;

class UserIsolationMiddleware
{
    protected $ldapAuthService;
    protected $sharingService;

    public function __construct(LdapAuthService $ldapAuthService, SharingService $sharingService)
    {
        $this->ldapAuthService = $ldapAuthService;
        $this->sharingService = $sharingService;
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

        $userModel = \App\Models\User::find($user['id']);
        if (!$userModel) {
            abort(401, 'User not found');
        }

        // Determine required access level
        $requiredAccess = $request->isMethod('GET') ? 'view' : 'edit';

        // For file operations, validate path access
        if ($request->has('path')) {
            $requestedPath = $request->input('path') ?? '';
            
            // Empty path is allowed for file operations (user's root folder)
            if (empty($requestedPath)) {
                return $next($request);
            }
            
            // Check direct ownership via LdapAuthService
            if ($this->ldapAuthService->hasPathAccess($userModel, $requestedPath)) {
                return $next($request);
            }

            // Check sharing access
            // If the path starts with 'users/', it's an absolute path that might belong to another user
            if (str_starts_with($requestedPath, 'users/')) {
                if ($this->sharingService->hasAccess($userModel, $requestedPath, $requiredAccess)) {
                    return $next($request);
                }
            }
            
            abort(403, 'Access denied to requested path');
        }

        // Validate file upload paths
        if ($request->isMethod('POST') && $request->hasFile('files')) {
            $uploadPath = $request->input('upload_path') ?? $request->input('path') ?? '';
            
            if (empty($uploadPath)) {
                 return $next($request);
            }

            if ($this->ldapAuthService->hasPathAccess($userModel, $uploadPath)) {
                return $next($request);
            }

            if (str_starts_with($uploadPath, 'users/')) {
                if ($this->sharingService->hasAccess($userModel, $uploadPath, 'edit')) {
                    return $next($request);
                }
            }

            abort(403, 'Access denied to upload path');
        }

        return $next($request);
    }
}

