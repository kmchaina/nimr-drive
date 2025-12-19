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

        try {
            // Get directories
            foreach ($this->disk->directories($userPath) as $directory) {
                $relativePath = str_replace($this->getUserBasePath($user) . '/', '', $directory);
                $directories[] = [
                    'name' => basename($directory),
                    'path' => $relativePath,
                    'type' => 'directory',
                    'size' => null,
                    'modified' => $this->getLastModified($directory),
                    'is_directory' => true,
                ];
            }

            // Get files
            foreach ($this->disk->files($userPath) as $file) {
                $relativePath = str_replace($this->getUserBasePath($user) . '/', '', $file);
                $files[] = [
                    'name' => basename($file),
                    'path' => $relativePath,
                    'type' => 'file',
                    'size' => $this->disk->size($file),
                    'size_formatted' => $this->formatBytes($this->disk->size($file)),
                    'mime_type' => $this->disk->mimeType($file),
                    'modified' => $this->getLastModified($file),
                    'is_directory' => false,
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
     * Delete a file or folder
     */
    public function delete(User $user, string $path): bool
    {
        $fullPath = $this->getUserPath($user, $path);

        if (!$this->disk->exists($fullPath)) {
            throw new \Exception("File or folder not found");
        }

        try {
            $isDirectory = $this->disk->directoryExists($fullPath);
            
            if ($isDirectory) {
                $result = $this->disk->deleteDirectory($fullPath);
                
                // Recalculate quota after directory deletion
                if ($result) {
                    $this->quotaService->recalculateUsage($user);
                }
            } else {
                $result = $this->disk->delete($fullPath);
                
                // For testing with fake files, recalculate quota after deletion
                if ($result) {
                    $this->quotaService->recalculateUsage($user);
                }
            }
            
            if ($result) {
                Log::info("Deleted {$fullPath} for user {$user->id}");
                // Clear cache for the parent directory
                $parentPath = dirname($path);
                if ($parentPath === '.') {
                    $parentPath = '';
                }
                $this->clearCache($user, $parentPath);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to delete {$fullPath}: " . $e->getMessage());
            throw new \Exception("Unable to delete: " . $e->getMessage());
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
    public function uploadFiles(User $user, ?string $path, array $files): array
    {
        $userPath = $this->getUserPath($user, $path ?? '');
        
        // Ensure the target directory exists (important for folder uploads)
        if (!$this->disk->exists($userPath)) {
            $this->disk->makeDirectory($userPath, 0755, true);
        }
        
        $results = [];
        $didUploadAtLeastOne = false;

        foreach ($files as $file) {
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

                // Validate file name
                $fileName = $file->getClientOriginalName();
                $this->validateFileName($fileName);

                // Check if file already exists
                $filePath = $userPath . '/' . $fileName;
                if ($this->disk->exists($filePath)) {
                    // Generate unique name
                    $fileName = $this->generateUniqueFileName($userPath, $fileName);
                    $filePath = $userPath . '/' . $fileName;
                }

                // Store the file
                $storedPath = $file->storeAs($userPath, $fileName, 'lacie');
                
                if ($storedPath) {
                    // For testing, use original file size since fake files may have 0 actual size
                    $actualFileSize = $this->disk->size($storedPath);
                    $sizeToUse = $actualFileSize > 0 ? $actualFileSize : $fileSize;
                    
                    // Update user's used bytes
                    $this->quotaService->updateUsedBytes($user, $sizeToUse);
                    $didUploadAtLeastOne = true;
                    
                    $results[] = [
                        'name' => $fileName,
                        'success' => true,
                        'size' => $fileSize,
                        'size_formatted' => $this->formatBytes($fileSize)
                    ];
                    
                    Log::info("Uploaded file {$fileName} for user {$user->id}");
                } else {
                    $results[] = [
                        'name' => $fileName,
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
     * Download a file
     */
    public function downloadFile(User $user, string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fullPath = $this->getUserPath($user, $path);

        if (!$this->disk->exists($fullPath) || $this->disk->directoryExists($fullPath)) {
            throw new \Exception("File not found");
        }

        try {
            $fileName = basename($fullPath);
            $mimeType = $this->disk->mimeType($fullPath);
            
            return $this->disk->download($fullPath, $fileName, [
                'Content-Type' => $mimeType,
            ]);
        } catch (\Exception $e) {
            Log::error("Download failed for {$fullPath}: " . $e->getMessage());
            throw new \Exception("Unable to download file: " . $e->getMessage());
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
        $basePath = $this->getUserBasePath($user);
        
        if (is_null($path) || empty($path) || $path === '/') {
            return $basePath;
        }
        
        // Validate and sanitize path using SecurityService
        $validation = $this->securityService->validatePath($path);
        if (!$validation['valid']) {
            throw new \Exception('Invalid path: ' . implode(', ', $validation['errors']));
        }
        
        $sanitizedPath = $validation['sanitized'];
        
        return $basePath . '/' . $sanitizedPath;
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