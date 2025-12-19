<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $quotaService;

    public function __construct(QuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Redirect to dashboard (admin panel is now integrated)
     */
    public function index()
    {
        return redirect('/dashboard');
    }

    /**
     * Update user quota
     */
    public function updateQuota(Request $request, User $user)
    {
        $request->validate([
            'quota_gb' => 'required|numeric|min:0.1|max:1000',
        ]);

        $quotaBytes = (int) ($request->quota_gb * 1024 * 1024 * 1024);
        
        $user->update([
            'quota_bytes' => $quotaBytes,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Quota updated to {$request->quota_gb} GB for {$user->display_name}",
            'user' => [
                'id' => $user->id,
                'quota_bytes' => $user->quota_bytes,
                'quota_formatted' => $this->formatBytes($user->quota_bytes),
                'usage_percentage' => $user->quota_bytes > 0 
                    ? round(($user->used_bytes / $user->quota_bytes) * 100, 1) 
                    : 0,
            ],
        ]);
    }

    /**
     * Toggle admin status
     */
    public function toggleAdmin(Request $request, User $user)
    {
        $currentUser = session('user');
        
        // Prevent removing own admin status
        if ($user->id === $currentUser['id']) {
            return response()->json([
                'success' => false,
                'error' => 'You cannot remove your own admin status',
            ], 400);
        }

        $user->update([
            'is_admin' => !$user->is_admin,
        ]);

        return response()->json([
            'success' => true,
            'message' => $user->is_admin 
                ? "{$user->display_name} is now an admin" 
                : "{$user->display_name} is no longer an admin",
            'is_admin' => $user->is_admin,
        ]);
    }

    /**
     * Recalculate user's storage usage
     */
    public function recalculateUsage(User $user)
    {
        $actualUsage = $this->quotaService->recalculateUsage($user);

        return response()->json([
            'success' => true,
            'message' => "Recalculated storage for {$user->display_name}",
            'used_bytes' => $actualUsage,
            'used_formatted' => $this->formatBytes($actualUsage),
            'usage_percentage' => $user->quota_bytes > 0 
                ? round(($actualUsage / $user->quota_bytes) * 100, 1) 
                : 0,
        ]);
    }

    /**
     * Bulk update quotas
     */
    public function bulkUpdateQuota(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'quota_gb' => 'required|numeric|min:0.1|max:1000',
        ]);

        $quotaBytes = (int) ($request->quota_gb * 1024 * 1024 * 1024);
        
        User::whereIn('id', $request->user_ids)->update([
            'quota_bytes' => $quotaBytes,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Updated quota to {$request->quota_gb} GB for " . count($request->user_ids) . " users",
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
