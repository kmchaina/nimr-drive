<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class QuotaService
{
    protected $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Check if user can upload a file of given size
     */
    public function canUpload(User $user, int $fileSize): bool
    {
        if ($user->quota_bytes <= 0) {
            return true; // Unlimited quota
        }

        $newUsedBytes = $user->used_bytes + $fileSize;
        return $newUsedBytes <= $user->quota_bytes;
    }

    /**
     * Update user's used bytes
     */
    public function updateUsedBytes(User $user, int $bytesChange): void
    {
        try {
            $newUsedBytes = max(0, $user->used_bytes + $bytesChange);
            
            $user->update(['used_bytes' => $newUsedBytes]);
            
            Log::info("Updated used bytes for user {$user->id}: {$bytesChange} bytes (new total: {$newUsedBytes})");
        } catch (\Exception $e) {
            Log::error("Failed to update used bytes for user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Recalculate user's actual storage usage
     */
    public function recalculateUsage(User $user): int
    {
        try {
            $actualUsage = $this->storageService->getUserStorageUsage($user);
            
            $user->update(['used_bytes' => $actualUsage]);
            
            Log::info("Recalculated storage usage for user {$user->id}: {$actualUsage} bytes");
            
            return $actualUsage;
        } catch (\Exception $e) {
            Log::error("Failed to recalculate usage for user {$user->id}: " . $e->getMessage());
            return $user->used_bytes;
        }
    }

    /**
     * Get quota information for user
     */
    public function getQuotaInfo(User $user): array
    {
        $usagePercentage = 0;
        if ($user->quota_bytes > 0) {
            $usagePercentage = ($user->used_bytes / $user->quota_bytes) * 100;
        }

        return [
            'used_bytes' => $user->used_bytes,
            'total_bytes' => $user->quota_bytes,
            'available_bytes' => max(0, $user->quota_bytes - $user->used_bytes),
            'usage_percentage' => round($usagePercentage, 2),
            'is_approaching_limit' => $usagePercentage >= 70,
            'has_exceeded' => $usagePercentage >= 95 && $user->quota_bytes > 0,
            'is_unlimited' => $user->quota_bytes <= 0,
            'used_formatted' => $this->formatBytes($user->used_bytes),
            'total_formatted' => $user->quota_bytes > 0 ? $this->formatBytes($user->quota_bytes) : 'Unlimited',
            'available_formatted' => $user->quota_bytes > 0 ? $this->formatBytes(max(0, $user->quota_bytes - $user->used_bytes)) : 'Unlimited',
        ];
    }

    /**
     * Set user quota
     */
    public function setQuota(User $user, int $quotaBytes): void
    {
        try {
            $user->update(['quota_bytes' => $quotaBytes]);
            
            Log::info("Set quota for user {$user->id}: {$quotaBytes} bytes ({$this->formatBytes($quotaBytes)})");
        } catch (\Exception $e) {
            Log::error("Failed to set quota for user {$user->id}: " . $e->getMessage());
            throw new \Exception("Unable to update quota: " . $e->getMessage());
        }
    }

    /**
     * Get users approaching quota limit
     */
    public function getUsersApproachingLimit(float $threshold = 80.0): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('quota_bytes', '>', 0)
            ->whereRaw('(used_bytes / quota_bytes) * 100 >= ?', [$threshold])
            ->get();
    }

    /**
     * Get users who have exceeded quota
     */
    public function getUsersExceededQuota(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('quota_bytes', '>', 0)
            ->whereRaw('used_bytes >= quota_bytes')
            ->get();
    }

    /**
     * Get quota statistics for all users
     */
    public function getQuotaStatistics(): array
    {
        $users = User::where('quota_bytes', '>', 0)->get();
        
        $totalQuota = $users->sum('quota_bytes');
        $totalUsed = $users->sum('used_bytes');
        $averageUsage = $users->count() > 0 ? $totalUsed / $users->count() : 0;
        
        $usersApproaching = $this->getUsersApproachingLimit(80)->count();
        $usersExceeded = $this->getUsersExceededQuota()->count();
        
        return [
            'total_users' => $users->count(),
            'total_quota' => $totalQuota,
            'total_used' => $totalUsed,
            'total_available' => max(0, $totalQuota - $totalUsed),
            'average_usage' => $averageUsage,
            'overall_usage_percentage' => $totalQuota > 0 ? round(($totalUsed / $totalQuota) * 100, 2) : 0,
            'users_approaching_limit' => $usersApproaching,
            'users_exceeded_quota' => $usersExceeded,
            'total_quota_formatted' => $this->formatBytes($totalQuota),
            'total_used_formatted' => $this->formatBytes($totalUsed),
            'total_available_formatted' => $this->formatBytes(max(0, $totalQuota - $totalUsed)),
            'average_usage_formatted' => $this->formatBytes($averageUsage),
        ];
    }

    /**
     * Validate quota before file operations
     */
    public function validateQuotaForOperation(User $user, string $operation, int $sizeChange = 0): void
    {
        if ($user->quota_bytes <= 0) {
            return; // Unlimited quota
        }

        switch ($operation) {
            case 'upload':
                if (!$this->canUpload($user, $sizeChange)) {
                    $available = max(0, $user->quota_bytes - $user->used_bytes);
                    throw new \Exception(
                        "Upload would exceed quota limit. Available: {$this->formatBytes($available)}, Required: {$this->formatBytes($sizeChange)}"
                    );
                }
                break;
                
            case 'copy':
                if (!$this->canUpload($user, $sizeChange)) {
                    throw new \Exception("Copy operation would exceed quota limit");
                }
                break;
        }
    }

    /**
     * Get quota usage trend (if we had historical data)
     */
    public function getUsageTrend(User $user, int $days = 30): array
    {
        // This would require a separate table to track usage history
        // For now, return current usage
        return [
            'current_usage' => $user->used_bytes,
            'trend' => 'stable', // Could be 'increasing', 'decreasing', 'stable'
            'projected_full_date' => null, // When quota might be full based on trend
        ];
    }

    /**
     * Clean up quota for deleted users
     */
    public function cleanupDeletedUsers(): int
    {
        // This would clean up quota records for users that no longer exist
        // Implementation depends on soft deletes or actual deletion strategy
        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Convert human readable size to bytes
     */
    public function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -2));
        $value = (float) substr($size, 0, -2);
        
        switch ($unit) {
            case 'KB':
                return (int) ($value * 1024);
            case 'MB':
                return (int) ($value * 1024 * 1024);
            case 'GB':
                return (int) ($value * 1024 * 1024 * 1024);
            case 'TB':
                return (int) ($value * 1024 * 1024 * 1024 * 1024);
            default:
                return (int) $value; // Assume bytes
        }
    }

    /**
     * Get recommended quota based on user role or other factors
     */
    public function getRecommendedQuota(User $user): int
    {
        // Default quota from config
        $defaultQuotaGb = config('ldap.default_quota_gb', 5);
        
        // Could be enhanced based on user role, department, etc.
        // For now, return default
        return $defaultQuotaGb * 1024 * 1024 * 1024; // Convert GB to bytes
    }
}