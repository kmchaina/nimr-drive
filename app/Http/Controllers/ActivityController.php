<?php

namespace App\Http\Controllers;

use App\Models\Recent;
use App\Models\Star;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    /**
     * Get recent items for the current user
     */
    public function recents(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $recents = Recent::where('user_id', $user->id)
            ->orderBy('accessed_at', 'desc')
            ->limit(20)
            ->get();

        $disk = Storage::disk('lacie');
        $items = [];

        foreach ($recents as $recent) {
            // Skip items that are in trash (contain /.trash/)
            if (str_contains($recent->path, '/.trash/')) {
                $recent->delete();
                continue;
            }
            
            if ($disk->exists($recent->path)) {
                // Calculate relative path for the user
                $relativePath = $recent->path;
                if (str_starts_with($recent->path, $user->folder_path . '/')) {
                    $relativePath = substr($recent->path, strlen($user->folder_path) + 1);
                }
                
                $items[] = [
                    'name' => basename($recent->path),
                    'path' => $relativePath,
                    'is_directory' => $recent->is_directory,
                    'accessed_at' => $recent->accessed_at,
                    'size_formatted' => $recent->is_directory ? null : $this->formatBytes($disk->size($recent->path)),
                    'modified' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($recent->path)),
                ];
            } else {
                // Cleanup if file no longer exists
                $recent->delete();
            }
        }

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    /**
     * Get starred items for the current user
     */
    public function starred(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $stars = Star::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $disk = Storage::disk('lacie');
        $items = [];

        foreach ($stars as $star) {
            // Skip items that are in trash (contain /.trash/)
            if (str_contains($star->path, '/.trash/')) {
                $star->delete();
                continue;
            }
            
            if ($disk->exists($star->path)) {
                // Calculate relative path for the user
                $relativePath = $star->path;
                if (str_starts_with($star->path, $user->folder_path . '/')) {
                    $relativePath = substr($star->path, strlen($user->folder_path) + 1);
                }
                
                $items[] = [
                    'name' => basename($star->path),
                    'path' => $relativePath,
                    'is_directory' => $star->is_directory,
                    'is_starred' => true,
                    'size_formatted' => $star->is_directory ? null : $this->formatBytes($disk->size($star->path)),
                    'modified' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($star->path)),
                ];
            } else {
                // Cleanup if file no longer exists
                $star->delete();
            }
        }

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    /**
     * Toggle star status for an item
     */
    public function toggleStar(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'is_directory' => 'required|boolean',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');
        
        // Resolve internal path if relative
        if (!str_starts_with($path, 'users/')) {
            $path = $user->folder_path . '/' . ltrim($path, '/');
        }

        $star = Star::where('user_id', $user->id)
            ->where('path', $path)
            ->first();

        if ($star) {
            $star->delete();
            // Clear cache for this path's parent
            $this->clearFileCache($user, $request->input('path'));
            return response()->json(['success' => true, 'is_starred' => false]);
        }

        Star::create([
            'user_id' => $user->id,
            'path' => $path,
            'is_directory' => $request->input('is_directory'),
        ]);

        // Clear cache for this path's parent
        $this->clearFileCache($user, $request->input('path'));

        return response()->json(['success' => true, 'is_starred' => true]);
    }

    /**
     * Clear file list cache for a path
     */
    private function clearFileCache(User $user, string $path)
    {
        $parentPath = dirname($path);
        if ($parentPath === '.' || $parentPath === '/') {
            $parentPath = '';
        }
        
        // Use the same logic as FileService to clear cache
        $cacheKey = 'file_list_' . $user->id . '_' . md5($parentPath);
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }

    /**
     * Record a recent activity
     */
    public static function recordActivity(User $user, string $path, bool $isDirectory)
    {
        // Resolve internal path if relative
        if (!str_starts_with($path, 'users/')) {
            $path = $user->folder_path . '/' . ltrim($path, '/');
        }

        Recent::updateOrCreate(
            ['user_id' => $user->id, 'path' => $path],
            ['is_directory' => $isDirectory, 'accessed_at' => now()]
        );

        // Keep only top 50 recents per user
        $count = Recent::where('user_id', $user->id)->count();
        if ($count > 50) {
            Recent::where('user_id', $user->id)
                ->orderBy('accessed_at', 'asc')
                ->limit($count - 50)
                ->delete();
        }
    }

    private function getCurrentUser(): User
    {
        $sessionUser = session('user');
        if (!$sessionUser) abort(401);
        return User::find($sessionUser['id']);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
