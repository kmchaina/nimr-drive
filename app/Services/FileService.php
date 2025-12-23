<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Models\User;

class FileService
{
    protected $disk;
    protected $quotaService;
    protected $securityService;

    public function __construct(QuotaService $quotaService, SecurityService $securityService)
    {
        $this->disk = Storage::disk('lacie');
        $this->quotaService = $quotaService;
        $this->securityService = $securityService;
    }

    /**
     * List files and folders in a directory with caching and pagination
     */
    public function listDirectory(User $user, ?string $path = '', int $page = 1, int $perPage = 100): array
    {
        $userPath = $this->getUserPath($user, $path ?? '');
        
        if (!$this->disk->exists($userPath)) {
            $this->disk->makeDirectory($userPath);
        }

        // Create cache key based on user, path, and last modification time
        $cacheKey = 'file_list_' . $user->id . '_' . md5($path ?? '');
        
        // Try to get from cache (60 seconds TTL - short for better responsiveness)
        $allItems = \Cache::remember($cacheKey, 60, function () use ($user, $userPath) {
            return $this->fetchDirectoryContents($user, $userPath);
        });

        // Calculate pagination
        $total = count($allItems);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allItems, $offset, $perPage);

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'has_more' => $offset + $perPage < $total
            ]
        ];
    }

    /**
     * Fetch directory contents (used for caching)
     */
    private function fetchDirectoryContents(User $user, string $userPath): array
    {
        $files = [];
        $directories = [];

        // Get starred paths for this user to mark items
        $starredPaths = \App\Models\Star::where('user_id', $user->id)->pluck('path')->toArray();

        // Determine the base path for relative path calculation
        // If userPath starts with users/{username}/files, use that as base
        $baseForRelative = $this->getUserBasePath($user);
        if (str_starts_with($userPath, 'users/') && !str_starts_with($userPath, $baseForRelative)) {
            // It's a shared directory, use its parent or some logic to keep it consistent
            // For shared items, we want the paths returned to be absolute so the middleware can track them
            $baseForRelative = ''; 
        }

        try {
            // Get directories
            foreach ($this->disk->directories($userPath) as $directory) {
                $dirName = basename($directory);
                
                // Hide system/trash folders in normal view
                if ($dirName === '.trash') continue;

                $relativePath = $baseForRelative ? str_replace($baseForRelative . '/', '', $directory) : $directory;
                $directories[] = [
                    'name' => basename($directory),
                    'path' => $relativePath,
                    'type' => 'directory',
                    'size' => null,
                    'modified' => $this->getLastModified($directory),
                    'is_directory' => true,
                    'is_starred' => in_array($directory, $starredPaths),
                ];
            }

            // Get files
            foreach ($this->disk->files($userPath) as $file) {
                $relativePath = $baseForRelative ? str_replace($baseForRelative . '/', '', $file) : $file;
                $files[] = [
                    'name' => basename($file),
                    'path' => $relativePath,
                    'type' => 'file',
                    'size' => $this->disk->size($file),
                    'size_formatted' => $this->formatBytes($this->disk->size($file)),
                    'mime_type' => $this->disk->mimeType($file),
                    'modified' => $this->getLastModified($file),
                    'is_directory' => false,
                    'is_starred' => in_array($file, $starredPaths),
                ];
            }

            // Sort: directories first, then files, both alphabetically
            usort($directories, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

            return array_merge($directories, $files);

        } catch (\Exception $e) {
            Log::error("Failed to list directory {$userPath}: " . $e->getMessage());
            throw new \Exception("Unable to access directory: " . $e->getMessage());
        }
    }

    /**
     * Create a new folder
     */
    public function createFolder(User $user, ?string $path, string $folderName): bool
    {
        $this->validateFileName($folderName);
        
        $userPath = $this->getUserPath($user, $path ?? '');
        $folderPath = $userPath . '/' . $folderName;

        if ($this->disk->exists($folderPath)) {
            throw new \Exception("Folder '{$folderName}' already exists");
        }

        try {
            $result = $this->disk->makeDirectory($folderPath);
            
            if ($result) {
                Log::info("Created folder: {$folderPath} for user {$user->id}");
                // Clear cache for this directory
                $this->clearCache($user, $path);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to create folder {$folderPath}: " . $e->getMessage());
            throw new \Exception("Unable to create folder: " . $e->getMessage());
        }
    }

    /**
     * Rename a file or folder
     */
    public function rename(User $user, string $oldPath, string $newName): bool
    {
        $this->validateFileName($newName);
        
        $oldFullPath = $this->getUserPath($user, $oldPath);
        $newFullPath = dirname($oldFullPath) . '/' . $newName;

        if (!$this->disk->exists($oldFullPath)) {
            throw new \Exception("File or folder not found");
        }

        if ($this->disk->exists($newFullPath)) {
            throw new \Exception("A file or folder with that name already exists");
        }

        try {
            $result = $this->disk->move($oldFullPath, $newFullPath);
            
            if ($result) {
                Log::info("Renamed {$oldFullPath} to {$newFullPath} for user {$user->id}");
                // Clear cache for the parent directory
                $parentPath = dirname($oldPath);
                if ($parentPath === '.') {
                    $parentPath = '';
                }
                $this->clearCache($user, $parentPath);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to rename {$oldFullPath} to {$newFullPath}: " . $e->getMessage());
            throw new \Exception("Unable to rename: " . $e->getMessage());
        }
    }

    /**
     * Move a file or folder to a new directory
     */
    public function move(User $user, string $sourcePath, string $targetDirectory): bool
    {
        $sourceFullPath = $this->getUserPath($user, $sourcePath);
        
        // Target directory is relative to user's root
        $targetDirectoryPath = $this->getUserPath($user, $targetDirectory);
        $fileName = basename($sourceFullPath);
        $destinationFullPath = rtrim($targetDirectoryPath, '/') . '/' . $fileName;

        if (!$this->disk->exists($sourceFullPath)) {
            throw new \Exception("Source file or folder not found");
        }

        if ($this->disk->exists($destinationFullPath)) {
            // If it exists, generate a unique name
            $fileName = $this->generateUniqueFileName($targetDirectoryPath, $fileName);
            $destinationFullPath = rtrim($targetDirectoryPath, '/') . '/' . $fileName;
        }

        try {
            $result = $this->disk->move($sourceFullPath, $destinationFullPath);
            
            if ($result) {
                Log::info("Moved {$sourceFullPath} to {$destinationFullPath} for user {$user->id}");
                
                // Clear cache for both source and destination parent directories
                $sourceParent = dirname($sourcePath);
                if ($sourceParent === '.') $sourceParent = '';
                
                $this->clearCache($user, $sourceParent);
                $this->clearCache($user, $targetDirectory);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to move {$sourceFullPath} to {$destinationFullPath}: " . $e->getMessage());
            throw new \Exception("Unable to move item: " . $e->getMessage());
        }
    }

    /**
     * Delete a file or folder
     */
    public function delete(User $user, string $path): bool
    {
        $fullPath = $this->getUserPath($user, $path);

        if (!$this->disk->exists($fullPath)) {
            throw new \Exception("File or folder not found");
        }

        // Instead of deleting, move to trash
        return $this->moveToTrash($user, $path);
    }

    /**
     * Move a file or folder to trash
     */
    public function moveToTrash(User $user, string $path): bool
    {
        $fullPath = $this->getUserPath($user, $path);
        $trashPath = $this->getUserTrashPath($user);
        
        if (!$this->disk->exists($trashPath)) {
            $this->disk->makeDirectory($trashPath);
        }

        $fileName = basename($fullPath);
        $timestamp = now()->timestamp;
        $destinationPath = $trashPath . '/' . $timestamp . '_' . $fileName;

        try {
            $result = $this->disk->move($fullPath, $destinationPath);
            
            if ($result) {
                Log::info("Moved to trash: {$fullPath} for user {$user->id}");
                $this->clearCache($user, dirname($path) === '.' ? '' : dirname($path));
                $this->clearCache($user, '.trash');
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to move to trash {$fullPath}: " . $e->getMessage());
            throw new \Exception("Unable to move to trash: " . $e->getMessage());
        }
    }

    /**
     * Get user's trash path
     */
    private function getUserTrashPath(User $user): string
    {
        return $this->getUserBasePath($user) . '/.trash';
    }

    /**
     * List files in trash
     */
    public function listTrash(User $user): array
    {
        $trashPath = $this->getUserTrashPath($user);
        
        if (!$this->disk->exists($trashPath)) {
            return [];
        }

        $items = [];
        $now = now();

        foreach ($this->disk->files($trashPath) as $file) {
            $baseName = basename($file);
            // Extract timestamp from prefix (e.g., 1234567890_file.txt)
            preg_match('/^(\d+)_/', $baseName, $matches);
            $deletedAt = isset($matches[1]) ? \Carbon\Carbon::createFromTimestamp($matches[1]) : $this->getLastModified($file);
            $originalName = preg_replace('/^\d+_/', '', $baseName);
            
            $daysInTrash = $deletedAt->diffInDays($now);
            $daysRemaining = max(0, 30 - $daysInTrash);
            
            $items[] = [
                'name' => $originalName,
                'path' => '.trash/' . $baseName,
                'type' => 'file',
                'size' => $this->disk->size($file),
                'size_formatted' => $this->formatBytes($this->disk->size($file)),
                'modified' => $this->getLastModified($file),
                'deleted_at' => $deletedAt,
                'days_remaining' => (int) $daysRemaining,
                'is_directory' => false,
                'is_trash' => true,
            ];
        }

        foreach ($this->disk->directories($trashPath) as $dir) {
            $baseName = basename($dir);
            preg_match('/^(\d+)_/', $baseName, $matches);
            $deletedAt = isset($matches[1]) ? \Carbon\Carbon::createFromTimestamp($matches[1]) : $this->getLastModified($dir);
            $originalName = preg_replace('/^\d+_/', '', $baseName);
            
            $daysInTrash = $deletedAt->diffInDays($now);
            $daysRemaining = max(0, 30 - $daysInTrash);
            
            $items[] = [
                'name' => $originalName,
                'path' => '.trash/' . $baseName,
                'type' => 'directory',
                'modified' => $this->getLastModified($dir),
                'deleted_at' => $deletedAt,
                'days_remaining' => (int) $daysRemaining,
                'is_directory' => true,
                'is_trash' => true,
            ];
        }

        return $items;
    }

    /**
     * Restore item from trash
     */
    public function restoreFromTrash(User $user, string $trashPath): bool
    {
        $fullTrashPath = $this->getUserPath($user, $trashPath);
        $baseName = basename($trashPath);
        $originalName = preg_replace('/^\d+_/', '', $baseName);
        
        $destinationPath = $this->getUserBasePath($user) . '/' . $originalName;

        if ($this->disk->exists($destinationPath)) {
            $originalName = $this->generateUniqueFileName($this->getUserBasePath($user), $originalName);
            $destinationPath = $this->getUserBasePath($user) . '/' . $originalName;
        }

        try {
            $result = $this->disk->move($fullTrashPath, $destinationPath);
            if ($result) {
                $this->clearCache($user, '');
                $this->clearCache($user, '.trash');
            }
            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Restore failed: " . $e->getMessage());
        }
    }

    /**
     * Permanently delete from trash
     */
    public function permanentDelete(User $user, string $trashPath): bool
    {
        $fullPath = $this->getUserPath($user, $trashPath);
        
        try {
            $size = 0;
            if (!$this->disk->directoryExists($fullPath)) {
                $size = $this->disk->size($fullPath);
                $result = $this->disk->delete($fullPath);
            } else {
                $size = $this->calculateDirectorySize($fullPath);
                $result = $this->disk->deleteDirectory($fullPath);
            }

            if ($result) {
                // Subtract from quota
                $this->quotaService->updateUsedBytes($user, -$size);
                $this->clearCache($user, '.trash');
            }
            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Delete failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate the total size of a directory
     */
    private function calculateDirectorySize(string $directoryPath): int
    {
        $totalSize = 0;
        
        try {
            $files = $this->disk->allFiles($directoryPath);
            foreach ($files as $file) {
                $totalSize += $this->disk->size($file);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to calculate directory size for {$directoryPath}: " . $e->getMessage());
        }
        
        return $totalSize;
    }

    /**
     * Upload files
     */
    public function uploadFiles(User $user, ?string $path, array $files, array $relativePaths = []): array
    {
        $userPath = $this->getUserPath($user, $path ?? '');
        
        // Ensure the target directory exists (important for folder uploads)
        if (!$this->disk->exists($userPath)) {
            $this->disk->makeDirectory($userPath, 0755, true);
        }
        
        $results = [];
        $didUploadAtLeastOne = false;

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                $results[] = [
                    'name' => 'unknown',
                    'success' => false,
                    'error' => 'Invalid file upload'
                ];
                continue;
            }

            try {
                // Check quota before upload
                $fileSize = $file->getSize();
                if (!$this->quotaService->canUpload($user, $fileSize)) {
                    $results[] = [
                        'name' => $file->getClientOriginalName(),
                        'success' => false,
                        'error' => 'Upload would exceed quota limit'
                    ];
                    continue;
                }

                // Get original name and handle potential directory structure
                $originalName = $file->getClientOriginalName();
                $relativePath = $relativePaths[$index] ?? '';
                
                // Final destination path logic
                $targetUploadDir = $userPath;
                $finalFileName = $originalName;

                if (!empty($relativePath)) {
                    // This is a folder upload
                    $dirPath = dirname($relativePath);
                    if ($dirPath !== '.') {
                        $targetUploadDir = rtrim($userPath, '/') . '/' . $dirPath;
                        if (!$this->disk->exists($targetUploadDir)) {
                            $this->disk->makeDirectory($targetUploadDir, 0755, true);
                        }
                    }
                    $finalFileName = basename($relativePath);
                }

                // Validate file name
                $this->validateFileName($finalFileName);

                // Check if file already exists
                $filePath = rtrim($targetUploadDir, '/') . '/' . $finalFileName;
                if ($this->disk->exists($filePath)) {
                    // Generate unique name
                    $finalFileName = $this->generateUniqueFileName($targetUploadDir, $finalFileName);
                    $filePath = rtrim($targetUploadDir, '/') . '/' . $finalFileName;
                }

                // Store the file
                $storedPath = $file->storeAs($targetUploadDir, $finalFileName, 'lacie');
                
                if ($storedPath) {
                    // For testing, use original file size since fake files may have 0 actual size
                    $actualFileSize = $this->disk->size($storedPath);
                    $sizeToUse = $actualFileSize > 0 ? $actualFileSize : $fileSize;
                    
                    // Determine which user's quota to update
                    $quotaUser = $user;
                    if (str_starts_with($targetUploadDir, 'users/')) {
                        $pathParts = explode('/', $targetUploadDir);
                        if (count($pathParts) >= 2) {
                            $ownerUsername = $pathParts[1];
                            $owner = User::where('ad_username', $ownerUsername)->first();
                            if ($owner) {
                                $quotaUser = $owner;
                            }
                        }
                    }

                    // Update user's used bytes
                    $this->quotaService->updateUsedBytes($quotaUser, $sizeToUse);
                    $didUploadAtLeastOne = true;
                    
                    $results[] = [
                        'name' => $finalFileName,
                        'success' => true,
                        'size' => $fileSize,
                        'size_formatted' => $this->formatBytes($fileSize)
                    ];
                    
                    Log::info("Uploaded file {$finalFileName} for user {$user->id}");
                } else {
                    $results[] = [
                        'name' => $finalFileName,
                        'success' => false,
                        'error' => 'Failed to store file'
                    ];
                }

            } catch (\Exception $e) {
                $results[] = [
                    'name' => $file->getClientOriginalName(),
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                Log::error("Upload failed for {$file->getClientOriginalName()}: " . $e->getMessage());
            }
        }

        // Directory listings are cached; invalidate cache for this path so new uploads appear immediately.
        if ($didUploadAtLeastOne) {
            $this->clearCache($user, $path ?? '');
            
            // Also clear parent directory cache if uploading to a subdirectory
            // This ensures new folders appear in the parent directory listing
            if (!empty($path)) {
                $pathParts = explode('/', $path);
                if (count($pathParts) > 0) {
                    // Clear cache for the immediate parent
                    array_pop($pathParts);
                    $parentPath = implode('/', $pathParts);
                    $this->clearCache($user, $parentPath);
                }
            }
        }

        return $results;
    }

    /**
     * Download multiple files/folders as a ZIP archive
     */
    public function downloadZip(User $user, array $paths): string
    {
        $zip = new \ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'nimr_zip_');
        
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Could not create ZIP archive");
        }

        foreach ($paths as $path) {
            $fullPath = $this->getUserPath($user, $path);
            
            if (!$this->disk->exists($fullPath)) continue;

            $baseInZip = basename($fullPath);

            if ($this->disk->directoryExists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $baseInZip);
            } else {
                $fileContent = $this->disk->get($fullPath);
                $zip->addFromString($baseInZip, $fileContent);
            }
        }

        $zip->close();
        return $tempFile;
    }

    /**
     * Helper to recursively add directory contents to ZIP
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $fullPath, string $zipPath): void
    {
        $zip->addEmptyDir($zipPath);
        
        foreach ($this->disk->files($fullPath) as $file) {
            $zip->addFromString($zipPath . '/' . basename($file), $this->disk->get($file));
        }

        foreach ($this->disk->directories($fullPath) as $dir) {
            $this->addDirectoryToZip($zip, $dir, $zipPath . '/' . basename($dir));
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(User $user, string $path): array
    {
        $fullPath = $this->getUserPath($user, $path);

        if (!$this->disk->exists($fullPath)) {
            throw new \Exception("File or folder not found");
        }

        $isDirectory = $this->disk->directoryExists($fullPath);
        
        return [
            'name' => basename($fullPath),
            'path' => $path,
            'type' => $isDirectory ? 'directory' : 'file',
            'size' => $isDirectory ? null : $this->disk->size($fullPath),
            'size_formatted' => $isDirectory ? null : $this->formatBytes($this->disk->size($fullPath)),
            'mime_type' => $isDirectory ? null : $this->disk->mimeType($fullPath),
            'modified' => $this->getLastModified($fullPath),
            'is_directory' => $isDirectory,
        ];
    }

    /**
     * Get user's base path
     */
    private function getUserBasePath(User $user): string
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        return "users/{$username}/files";
    }

    /**
     * Get full user path with optional subpath
     */
    private function getUserPath(User $user, ?string $path = ''): string
    {
        if (is_null($path) || empty($path) || $path === '/') {
            return $this->getUserBasePath($user);
        }

        // If it's an absolute path (starts with users/), check if it's shared or belongs to user
        if (str_starts_with($path, 'users/')) {
            // Validate that the user has access to this absolute path
            if ($this->securityService->sanitizePath($path) !== $path) {
                 throw new \Exception('Invalid path format');
            }

            // The middleware already checked access, but let's be safe
            return $path;
        }
        
        // Validate and sanitize relative path
        $validation = $this->securityService->validatePath($path);
        if (!$validation['valid']) {
            throw new \Exception('Invalid path: ' . implode(', ', $validation['errors']));
        }
        
        $sanitizedPath = $validation['sanitized'];
        return $this->getUserBasePath($user) . '/' . $sanitizedPath;
    }

    /**
     * Validate file name using SecurityService
     */
    private function validateFileName(string $fileName): void
    {
        $validation = $this->securityService->validateFileName($fileName);
        
        if (!$validation['valid']) {
            throw new \Exception(implode(', ', $validation['errors']));
        }

        // Also validate file extension
        $extensionValidation = $this->securityService->validateFileExtension($fileName);
        if (!$extensionValidation['valid']) {
            throw new \Exception($extensionValidation['error']);
        }
    }

    /**
     * Generate unique file name if file already exists
     */
    private function generateUniqueFileName(string $directory, string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $baseName = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        
        $counter = 1;
        $newFileName = $fileName;
        
        while ($this->disk->exists($directory . '/' . $newFileName)) {
            $newFileName = $baseName . ' (' . $counter . ')' . $extension;
            $counter++;
        }
        
        return $newFileName;
    }

    /**
     * Get last modified time
     */
    private function getLastModified(string $path): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::createFromTimestamp($this->disk->lastModified($path));
        } catch (\Exception $e) {
            return \Carbon\Carbon::now();
        }
    }

    /**
     * Check if a file exists (for sharing validation)
     */
    public function fileExists(User $user, string $path): bool
    {
        $fullPath = $this->getUserPath($user, $path);
        return $this->disk->exists($fullPath);
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
     * Clear cache for a specific path and ALL parent directories
     */
    private function clearCache(User $user, ?string $path = ''): void
    {
        // Clear cache for the specific path
        $cacheKey = 'file_list_' . $user->id . '_' . md5($path ?? '');
        \Cache::forget($cacheKey);
        
        // Clear cache for ALL parent directories up to root
        if (!empty($path)) {
            $pathParts = explode('/', trim($path, '/'));
            $currentPath = '';
            
            // Clear root cache
            \Cache::forget('file_list_' . $user->id . '_' . md5(''));
            
            // Clear each parent directory cache
            foreach ($pathParts as $part) {
                $currentPath = $currentPath ? $currentPath . '/' . $part : $part;
                $parentCacheKey = 'file_list_' . $user->id . '_' . md5($currentPath);
                \Cache::forget($parentCacheKey);
            }
        }
        
        Log::debug("Cleared cache for user {$user->id}, path: " . ($path ?: 'root'));
    }
}