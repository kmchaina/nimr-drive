<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class ErrorHandlerService
{
    /**
     * Handle file system errors and return user-friendly messages
     */
    public function handleFileSystemError(Exception $e): array
    {
        $message = $e->getMessage();
        $userMessage = 'An error occurred while processing your request';
        $canRetry = false;

        // Disk full scenarios
        if ($this->isDiskFullError($message)) {
            $userMessage = 'Storage is full. Please delete some files or contact your administrator to increase your quota.';
            $canRetry = false;
            Log::error('Disk full error: ' . $message);
        }
        // Permission errors
        elseif ($this->isPermissionError($message)) {
            $userMessage = 'You do not have permission to perform this operation.';
            $canRetry = false;
            Log::error('Permission error: ' . $message);
        }
        // File not found
        elseif ($this->isFileNotFoundError($message)) {
            $userMessage = 'The requested file or folder could not be found.';
            $canRetry = false;
            Log::warning('File not found: ' . $message);
        }
        // File already exists
        elseif ($this->isFileExistsError($message)) {
            $userMessage = 'A file or folder with this name already exists.';
            $canRetry = false;
            Log::info('File exists error: ' . $message);
        }
        // Network/connectivity issues
        elseif ($this->isConnectivityError($message)) {
            $userMessage = 'Connection error. Please check your network and try again.';
            $canRetry = true;
            Log::warning('Connectivity error: ' . $message);
        }
        // Timeout errors
        elseif ($this->isTimeoutError($message)) {
            $userMessage = 'The operation timed out. Please try again.';
            $canRetry = true;
            Log::warning('Timeout error: ' . $message);
        }
        // Quota exceeded
        elseif ($this->isQuotaError($message)) {
            $userMessage = 'This operation would exceed your storage quota. Please delete some files first.';
            $canRetry = false;
            Log::info('Quota exceeded: ' . $message);
        }
        // Invalid file name/path
        elseif ($this->isValidationError($message)) {
            $userMessage = $message; // Use the specific validation message
            $canRetry = false;
            Log::info('Validation error: ' . $message);
        }
        // Generic errors
        else {
            Log::error('Unhandled file system error: ' . $message, [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [
            'success' => false,
            'error' => $userMessage,
            'can_retry' => $canRetry,
            'error_code' => $this->getErrorCode($e)
        ];
    }

    /**
     * Check if error is disk full
     */
    private function isDiskFullError(string $message): bool
    {
        $patterns = [
            'disk full',
            'no space left',
            'insufficient space',
            'quota exceeded',
            'storage full'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is permission related
     */
    private function isPermissionError(string $message): bool
    {
        $patterns = [
            'permission denied',
            'access denied',
            'forbidden',
            'not authorized',
            'insufficient permissions'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is file not found
     */
    private function isFileNotFoundError(string $message): bool
    {
        $patterns = [
            'not found',
            'does not exist',
            'no such file',
            'cannot find'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is file already exists
     */
    private function isFileExistsError(string $message): bool
    {
        $patterns = [
            'already exists',
            'file exists',
            'duplicate'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is connectivity related
     */
    private function isConnectivityError(string $message): bool
    {
        $patterns = [
            'connection',
            'network',
            'unreachable',
            'could not connect'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is timeout
     */
    private function isTimeoutError(string $message): bool
    {
        $patterns = [
            'timeout',
            'timed out',
            'time limit exceeded'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is quota related
     */
    private function isQuotaError(string $message): bool
    {
        $patterns = [
            'quota',
            'storage limit',
            'exceeds limit'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is validation related
     */
    private function isValidationError(string $message): bool
    {
        $patterns = [
            'invalid',
            'validation',
            'cannot be empty',
            'too long',
            'reserved',
            'contains invalid'
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get error code for categorization
     */
    private function getErrorCode(Exception $e): string
    {
        $message = $e->getMessage();

        if ($this->isDiskFullError($message)) return 'DISK_FULL';
        if ($this->isPermissionError($message)) return 'PERMISSION_DENIED';
        if ($this->isFileNotFoundError($message)) return 'FILE_NOT_FOUND';
        if ($this->isFileExistsError($message)) return 'FILE_EXISTS';
        if ($this->isConnectivityError($message)) return 'CONNECTIVITY_ERROR';
        if ($this->isTimeoutError($message)) return 'TIMEOUT';
        if ($this->isQuotaError($message)) return 'QUOTA_EXCEEDED';
        if ($this->isValidationError($message)) return 'VALIDATION_ERROR';

        return 'UNKNOWN_ERROR';
    }

    /**
     * Format validation errors for user display
     */
    public function formatValidationErrors(array $errors): string
    {
        if (empty($errors)) {
            return 'Validation failed';
        }

        if (count($errors) === 1) {
            return $errors[0];
        }

        return implode('. ', $errors);
    }
}
