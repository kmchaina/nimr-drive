<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StorageService
{
    protected $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('lacie');
    }

    /**
     * Get disk usage statistics
     */
    public function getDiskUsage(): array
    {
        try {
            $rootPath = $this->disk->path('');
            
            if (!is_dir($rootPath)) {
                return [
                    'total_space' => 0,
                    'free_space' => 0,
                    'used_space' => 0,
                    'usage_percentage' => 0,
                ];
            }

            $totalSpace = disk_total_space($rootPath);
            $freeSpace = disk_free_space($rootPath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = $totalSpace > 0 ? ($usedSpace / $totalSpace) * 100 : 0;

            return [
                'total_space' => $totalSpace,
                'free_space' => $freeSpace,
                'used_space' => $usedSpace,
                'usage_percentage' => round($usagePercentage, 2),
                'total_formatted' => $this->formatBytes($totalSpace),
                'free_formatted' => $this->formatBytes($freeSpace),
                'used_formatted' => $this->formatBytes($usedSpace),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get disk usage: " . $e->getMessage());
            return [
                'total_space' => 0,
                'free_space' => 0,
                'used_space' => 0,
                'usage_percentage' => 0,
                'total_formatted' => '0 B',
                'free_formatted' => '0 B',
                'used_formatted' => '0 B',
            ];
        }
    }

    /**
     * Calculate directory size recursively
     */
    public function getDirectorySize(string $path): int
    {
        try {
            $totalSize = 0;
            
            // Get all files in directory recursively
            $files = $this->disk->allFiles($path);
            
            foreach ($files as $file) {
                $totalSize += $this->disk->size($file);
            }
            
            return $totalSize;
        } catch (\Exception $e) {
            Log::error("Failed to calculate directory size for {$path}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user's actual storage usage
     */
    public function getUserStorageUsage(User $user): int
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        $userPath = "users/{$username}/files";
        
        if (!$this->disk->exists($userPath)) {
            return 0;
        }
        
        return $this->getDirectorySize($userPath);
    }

    /**
     * Ensure user directory exists
     */
    public function ensureUserDirectory(User $user): bool
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        $userPath = "users/{$username}/files";
        
        try {
            if (!$this->disk->exists($userPath)) {
                $result = $this->disk->makeDirectory($userPath);
                
                if ($result) {
                    Log::info("Created user directory: {$userPath}");
                }
                
                return $result;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to ensure user directory {$userPath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if path is within user's allowed directory
     */
    public function isPathAllowed(User $user, string $path): bool
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        $userBasePath = "users/{$username}/files";
        
        // Normalize paths
        $path = ltrim($path, '/\\');
        $userBasePath = ltrim($userBasePath, '/\\');
        
        // Empty path means user's root directory
        if (empty($path)) {
            return true;
        }
        
        // Check if path starts with user's base path
        return str_starts_with($path, $userBasePath);
    }

    /**
     * Get file system information
     */
    public function getFileSystemInfo(): array
    {
        try {
            $rootPath = $this->disk->path('');
            
            return [
                'root_path' => $rootPath,
                'driver' => 'local',
                'writable' => is_writable($rootPath),
                'exists' => is_dir($rootPath),
                'permissions' => $this->getPathPermissions($rootPath),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get file system info: " . $e->getMessage());
            return [
                'root_path' => '',
                'driver' => 'local',
                'writable' => false,
                'exists' => false,
                'permissions' => null,
            ];
        }
    }

    /**
     * Get path permissions
     */
    private function getPathPermissions(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        
        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Clean up empty directories
     */
    public function cleanupEmptyDirectories(string $path): int
    {
        $cleaned = 0;
        
        try {
            $directories = $this->disk->directories($path);
            
            foreach ($directories as $directory) {
                // Recursively clean subdirectories first
                $cleaned += $this->cleanupEmptyDirectories($directory);
                
                // Check if directory is now empty
                $files = $this->disk->files($directory);
                $subdirs = $this->disk->directories($directory);
                
                if (empty($files) && empty($subdirs)) {
                    if ($this->disk->deleteDirectory($directory)) {
                        $cleaned++;
                        Log::info("Cleaned up empty directory: {$directory}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to cleanup directories in {$path}: " . $e->getMessage());
        }
        
        return $cleaned;
    }

    /**
     * Get storage health status
     */
    public function getStorageHealth(): array
    {
        $diskUsage = $this->getDiskUsage();
        $fsInfo = $this->getFileSystemInfo();
        
        $health = 'good';
        $issues = [];
        
        // Check disk space
        if ($diskUsage['usage_percentage'] > 95) {
            $health = 'critical';
            $issues[] = 'Disk space critically low (>95% used)';
        } elseif ($diskUsage['usage_percentage'] > 85) {
            $health = 'warning';
            $issues[] = 'Disk space running low (>85% used)';
        }
        
        // Check file system accessibility
        if (!$fsInfo['exists']) {
            $health = 'critical';
            $issues[] = 'Storage directory does not exist';
        } elseif (!$fsInfo['writable']) {
            $health = 'critical';
            $issues[] = 'Storage directory is not writable';
        }
        
        return [
            'status' => $health,
            'issues' => $issues,
            'disk_usage' => $diskUsage,
            'file_system' => $fsInfo,
            'timestamp' => now(),
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

    /**
     * Get disk instance
     */
    public function getDisk()
    {
        return $this->disk;
    }

    /**
     * Test storage connectivity
     */
    public function testConnectivity(): bool
    {
        try {
            $testFile = 'connectivity_test_' . time() . '.txt';
            $testContent = 'Storage connectivity test';
            
            // Try to write a test file
            $result = $this->disk->put($testFile, $testContent);
            
            if ($result) {
                // Try to read it back
                $readContent = $this->disk->get($testFile);
                
                // Clean up
                $this->disk->delete($testFile);
                
                return $readContent === $testContent;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Storage connectivity test failed: " . $e->getMessage());
            return false;
        }
    }
}