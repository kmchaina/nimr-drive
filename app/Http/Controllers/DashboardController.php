<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sessionUser = session('user');
        
        if (!$sessionUser) {
            return redirect('/login');
        }

        // Get fresh user data from database (for is_admin and updated quota)
        $dbUser = User::find($sessionUser['id']);
        if (!$dbUser) {
            return redirect('/login');
        }

        // Merge session data with fresh DB data
        $user = array_merge($sessionUser, [
            'is_admin' => $dbUser->is_admin,
            'used_bytes' => $dbUser->used_bytes,
            'quota_bytes' => $dbUser->quota_bytes,
        ]);

        // Calculate quota usage percentage
        $quotaUsagePercentage = 0;
        if ($user['quota_bytes'] > 0) {
            $quotaUsagePercentage = ($user['used_bytes'] / $user['quota_bytes']) * 100;
        }

        // Check if user is approaching quota limit
        $isApproachingLimit = $quotaUsagePercentage > 80;
        $hasExceededQuota = $user['used_bytes'] >= $user['quota_bytes'];

        // Get basic file listing for user's directory
        $disk = Storage::disk('lacie');
        $username = $dbUser->ad_username ?: (string) $dbUser->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        $userPath = "users/{$username}/files";
        
        // Ensure user directory exists
        if (!$disk->exists($userPath)) {
            $disk->makeDirectory($userPath);
        }

        // Prepare admin data if user is admin
        $adminData = null;
        if ($dbUser->is_admin) {
            $adminData = $this->getAdminData();
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'quota' => [
                'used_bytes' => $user['used_bytes'],
                'total_bytes' => $user['quota_bytes'],
                'usage_percentage' => round($quotaUsagePercentage, 2),
                'is_approaching_limit' => $isApproachingLimit,
                'has_exceeded' => $hasExceededQuota,
                'used_formatted' => $this->formatBytes($user['used_bytes']),
                'total_formatted' => $this->formatBytes($user['quota_bytes']),
            ],
            'user_path' => $userPath,
            'adminData' => $adminData,
        ]);
    }

    /**
     * Get admin panel data
     */
    private function getAdminData(): array
    {
        $users = User::orderBy('name')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'ad_username' => $user->ad_username,
                'quota_bytes' => $user->quota_bytes,
                'used_bytes' => $user->used_bytes,
                'quota_formatted' => $this->formatBytes($user->quota_bytes),
                'used_formatted' => $this->formatBytes($user->used_bytes),
                'usage_percentage' => $user->quota_bytes > 0 
                    ? round(($user->used_bytes / $user->quota_bytes) * 100, 1) 
                    : 0,
                'is_admin' => $user->is_admin,
                'last_login' => $user->last_login?->format('M d, Y H:i'),
                'created_at' => $user->created_at?->format('M d, Y'),
            ];
        });

        $stats = [
            'total_users' => User::count(),
            'total_storage_used' => $this->formatBytes(User::sum('used_bytes')),
            'total_storage_allocated' => $this->formatBytes(User::sum('quota_bytes')),
            'admin_count' => User::where('is_admin', true)->count(),
        ];

        return [
            'users' => $users,
            'stats' => $stats,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}