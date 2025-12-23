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
    protected $description = 'Permanently delete items in trash that are older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle(FileService $fileService)
    {
        $this->info('Starting trash cleanup...');
        $disk = Storage::disk('lacie');
        $now = now();
        $expiryDays = 30;
        $deletedCount = 0;

        // Iterate through all users
        User::chunk(100, function ($users) use ($disk, $now, $expiryDays, $fileService, &$deletedCount) {
            foreach ($users as $user) {
                $trashPath = "users/{$user->ad_username}/files/.trash";
                
                if (!$disk->exists($trashPath)) {
                    continue;
                }

                // Process files
                foreach ($disk->files($trashPath) as $file) {
                    $baseName = basename($file);
                    if (preg_match('/^(\d+)_/', $baseName, $matches)) {
                        $deletedAt = \Carbon\Carbon::createFromTimestamp($matches[1]);
                        if ($deletedAt->diffInDays($now) >= $expiryDays) {
                            $this->line("Deleting expired file: {$baseName} for user {$user->ad_username}");
                            $fileService->permanentDelete($user, '.trash/' . $baseName);
                            $deletedCount++;
                        }
                    }
                }

                // Process directories
                foreach ($disk->directories($trashPath) as $dir) {
                    $baseName = basename($dir);
                    if (preg_match('/^(\d+)_/', $baseName, $matches)) {
                        $deletedAt = \Carbon\Carbon::createFromTimestamp($matches[1]);
                        if ($deletedAt->diffInDays($now) >= $expiryDays) {
                            $this->line("Deleting expired folder: {$baseName} for user {$user->ad_username}");
                            $fileService->permanentDelete($user, '.trash/' . $baseName);
                            $deletedCount++;
                        }
                    }
                }
            }
        });

        $this->info("Trash cleanup finished. Deleted {$deletedCount} items.");
        Log::info("Trash cleanup finished. Deleted {$deletedCount} items.");
    }
}
