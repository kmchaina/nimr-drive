<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete items in trash older than 30 days and clean up temp upload files';

    /**
     * Execute the console command.
     */
    public function handle(FileService $fileService)
    {
        $this->info('Starting cleanup...');
        
        // Clean trash
        $trashCount = $this->cleanupTrash($fileService);
        
        // Clean temp upload files
        $tempCount = $this->cleanupTempUploads();
        
        $this->info("Cleanup finished. Deleted {$trashCount} trash items and {$tempCount} temp upload folders.");
        Log::info("Cleanup finished. Deleted {$trashCount} trash items and {$tempCount} temp upload folders.");
    }
    
    /**
     * Clean up expired trash items
     */
    private function cleanupTrash(FileService $fileService): int
    {
        $this->info('Cleaning up expired trash items...');
        $disk = Storage::disk('lacie');
        $now = now();
        $expiryDays = 30;
        $deletedCount = 0;

        // Iterate through all users
        User::chunk(100, function ($users) use ($disk, $now, $expiryDays, $fileService, &$deletedCount) {
            foreach ($users as $user) {
                $username = $user->ad_username ?: (string) $user->id;
                $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
                $trashPath = "users/{$username}/files/.trash";
                
                if (!$disk->exists($trashPath)) {
                    continue;
                }

                // Process files
                foreach ($disk->files($trashPath) as $file) {
                    $baseName = basename($file);
                    if (preg_match('/^(\d+)_/', $baseName, $matches)) {
                        $deletedAt = \Carbon\Carbon::createFromTimestamp($matches[1]);
                        if ($deletedAt->diffInDays($now) >= $expiryDays) {
                            $this->line("Deleting expired file: {$baseName} for user {$username}");
                            try {
                                $fileService->permanentDelete($user, '.trash/' . $baseName);
                                $deletedCount++;
                            } catch (\Exception $e) {
                                $this->error("Failed to delete {$baseName}: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Process directories
                foreach ($disk->directories($trashPath) as $dir) {
                    $baseName = basename($dir);
                    if (preg_match('/^(\d+)_/', $baseName, $matches)) {
                        $deletedAt = \Carbon\Carbon::createFromTimestamp($matches[1]);
                        if ($deletedAt->diffInDays($now) >= $expiryDays) {
                            $this->line("Deleting expired folder: {$baseName} for user {$username}");
                            try {
                                $fileService->permanentDelete($user, '.trash/' . $baseName);
                                $deletedCount++;
                            } catch (\Exception $e) {
                                $this->error("Failed to delete {$baseName}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        });
        
        return $deletedCount;
    }
    
    /**
     * Clean up stale temp upload files (older than 24 hours)
     */
    private function cleanupTempUploads(): int
    {
        $this->info('Cleaning up stale temp upload files...');
        $tempDir = storage_path('app/temp/uploads');
        $deletedCount = 0;
        $now = now();
        $maxAgeHours = 24;
        
        if (!is_dir($tempDir)) {
            return 0;
        }
        
        $directories = glob($tempDir . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $metaFile = $dir . '/meta.json';
            
            if (file_exists($metaFile)) {
                $meta = json_decode(file_get_contents($metaFile), true);
                $createdAt = isset($meta['created_at']) 
                    ? \Carbon\Carbon::parse($meta['created_at']) 
                    : \Carbon\Carbon::createFromTimestamp(filemtime($metaFile));
                
                if ($createdAt->diffInHours($now) >= $maxAgeHours) {
                    $this->line("Deleting stale upload: " . basename($dir));
                    $this->deleteDirectory($dir);
                    $deletedCount++;
                }
            } else {
                // No meta file, check directory modification time
                $modTime = \Carbon\Carbon::createFromTimestamp(filemtime($dir));
                if ($modTime->diffInHours($now) >= $maxAgeHours) {
                    $this->line("Deleting orphaned upload: " . basename($dir));
                    $this->deleteDirectory($dir);
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}
