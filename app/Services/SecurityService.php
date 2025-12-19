<?php

namespace App\Services;

use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Allowed file extensions
     */
    private const ALLOWED_EXTENSIONS = [
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods', 'odp',
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2',
        // Audio/Video
        'mp3', 'wav', 'ogg', 'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm',
        // Code
        'html', 'css', 'js', 'json', 'xml', 'csv', 'sql', 'php', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go', 'rb', 'swift',
        // Other
        'md', 'log', 'ini', 'cfg', 'conf', 'yaml', 'yml',
    ];

    /**
     * Dangerous file extensions that should be blocked
     */
    private const BLOCKED_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'msi', 'dll', 'sh', 'bash',
    ];

    /**
     * Validate file name for security issues
     */
    public function validateFileName(string $fileName): array
    {
        $errors = [];

        // Check for empty name
        if (empty(trim($fileName))) {
            $errors[] = 'File name cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check length
        if (strlen($fileName) > 255) {
            $errors[] = 'File name is too long (maximum 255 characters)';
        }

        // Check for directory traversal attempts
        if ($this->containsDirectoryTraversal($fileName)) {
            $errors[] = 'File name contains invalid characters (directory traversal attempt)';
        }

        // Check for null bytes
        if (strpos($fileName, "\0") !== false) {
            $errors[] = 'File name contains null bytes';
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $fileName)) {
            $errors[] = 'File name contains control characters';
        }

        // Check for reserved Windows names
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (in_array(strtoupper($baseName), $reservedNames)) {
            $errors[] = 'File name uses a reserved system name';
        }

        // Check for dangerous characters
        if (preg_match('/[<>:"|?*]/', $fileName)) {
            $errors[] = 'File name contains invalid characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $this->sanitizeFileName($fileName)
        ];
    }

    /**
     * Validate file extension
     */
    public function validateFileExtension(string $fileName): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check if extension is blocked
        if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
            return [
                'valid' => false,
                'error' => "File type '.{$extension}' is not allowed for security reasons"
            ];
        }

        // For now, we'll allow all non-blocked extensions
        // You can uncomment the following to enforce whitelist
        /*
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'valid' => false,
                'error' => "File type '.{$extension}' is not allowed"
            ];
        }
        */

        return ['valid' => true];
    }

    /**
     * Validate and sanitize path
     */
    public function validatePath(string $path): array
    {
        $errors = [];

        // Check for directory traversal
        if ($this->containsDirectoryTraversal($path)) {
            $errors[] = 'Path contains directory traversal attempt';
        }

        // Check for absolute paths
        if (Str::startsWith($path, '/') || Str::startsWith($path, '\\') || preg_match('/^[a-zA-Z]:/', $path)) {
            $errors[] = 'Absolute paths are not allowed';
        }

        // Check for null bytes
        if (strpos($path, "\0") !== false) {
            $errors[] = 'Path contains null bytes';
        }

        // Sanitize the path
        $sanitized = $this->sanitizePath($path);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }

    /**
     * Check if string contains directory traversal attempts
     */
    private function containsDirectoryTraversal(string $input): bool
    {
        // Check for ../ or ..\
        if (strpos($input, '..') !== false) {
            return true;
        }

        // Check for encoded traversal attempts
        $decoded = urldecode($input);
        if (strpos($decoded, '..') !== false) {
            return true;
        }

        // Check for double encoded
        $doubleDecoded = urldecode($decoded);
        if (strpos($doubleDecoded, '..') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize file name
     */
    public function sanitizeFileName(string $fileName): string
    {
        // Remove any path components
        $fileName = basename($fileName);

        // Remove null bytes
        $fileName = str_replace("\0", '', $fileName);

        // Remove control characters
        $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName);

        // Remove dangerous characters
        $fileName = preg_replace('/[<>:"|?*]/', '', $fileName);

        // Trim whitespace and dots
        $fileName = trim($fileName, " \t\n\r\0\x0B.");

        // If empty after sanitization, generate a random name
        if (empty($fileName)) {
            $fileName = 'file_' . time() . '_' . Str::random(8);
        }

        return $fileName;
    }

    /**
     * Sanitize path
     */
    public function sanitizePath(string $path): string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);

        // Normalize slashes
        $path = str_replace('\\', '/', $path);

        // Remove any ../ or ../
        $path = preg_replace('#/\.\.(/|$)#', '/', $path);
        $path = preg_replace('#^\.\./#', '', $path);

        // Remove multiple slashes
        $path = preg_replace('#/+#', '/', $path);

        // Remove leading/trailing slashes
        $path = trim($path, '/');

        return $path;
    }

    /**
     * Validate file size
     */
    public function validateFileSize(int $size, int $maxSize = 104857600): array
    {
        if ($size > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize)
            ];
        }

        if ($size <= 0) {
            return [
                'valid' => false,
                'error' => 'Invalid file size'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
