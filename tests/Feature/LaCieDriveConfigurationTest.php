<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature: web-file-manager, Property 11: LaCie drive direct operations
 * 
 * Property: For any file operation (upload, download, modify), the system should 
 * perform the operation directly on the LaCie drive without copying files to Laravel storage directories
 * 
 * Validates: Requirements 8.4, 10.1, 10.2, 10.3, 10.4
 */
class LaCieDriveConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure LaCie disk is configured
        config(['filesystems.disks.lacie.root' => storage_path('testing/lacie')]);
        
        // Create test directory structure
        if (!file_exists(storage_path('testing/lacie'))) {
            mkdir(storage_path('testing/lacie'), 0755, true);
        }
        if (!file_exists(storage_path('testing/lacie/users'))) {
            mkdir(storage_path('testing/lacie/users'), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupTestDirectory(storage_path('testing'));
        parent::tearDown();
    }

    private function cleanupTestDirectory(string $path): void
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->cleanupTestDirectory($fullPath);
                } else {
                    unlink($fullPath);
                }
            }
            rmdir($path);
        }
    }

    /**
     * Test that LaCie disk is properly configured
     */
    public function test_lacie_disk_is_configured(): void
    {
        $disk = Storage::disk('lacie');
        
        $this->assertNotNull($disk);
        $this->assertEquals('local', config('filesystems.disks.lacie.driver'));
        $this->assertNotNull(config('filesystems.disks.lacie.root'));
    }

    /**
     * Property Test: File operations use LaCie drive directly
     * 
     * For any file operation, verify it operates on the LaCie drive path
     */
    public function test_file_operations_use_lacie_drive_directly(): void
    {
        $disk = Storage::disk('lacie');
        $testUserId = 1;
        $userPath = "users/{$testUserId}/files";
        
        // Ensure user directory exists
        $disk->makeDirectory($userPath);
        
        // Test file creation on LaCie drive
        $testFileName = 'test_file.txt';
        $testContent = 'This is test content for property testing';
        $filePath = "{$userPath}/{$testFileName}";
        
        // Property: File creation should store directly on LaCie drive
        $result = $disk->put($filePath, $testContent);
        $this->assertTrue($result);
        
        // Verify file exists on LaCie drive (not in Laravel storage)
        $this->assertTrue($disk->exists($filePath));
        $this->assertEquals($testContent, $disk->get($filePath));
        
        // Verify file is NOT in Laravel storage directories
        $this->assertFalse(Storage::disk('local')->exists($filePath));
        $this->assertFalse(Storage::disk('public')->exists($filePath));
        
        // Property: File modification should happen directly on LaCie drive
        $modifiedContent = 'Modified content for property testing';
        $disk->put($filePath, $modifiedContent);
        $this->assertEquals($modifiedContent, $disk->get($filePath));
        
        // Property: File deletion should remove from LaCie drive
        $disk->delete($filePath);
        $this->assertFalse($disk->exists($filePath));
    }

    /**
     * Property Test: Multiple file operations maintain LaCie drive isolation
     * 
     * For any sequence of file operations, verify they all use LaCie drive
     */
    public function test_multiple_operations_maintain_lacie_isolation(): void
    {
        $disk = Storage::disk('lacie');
        $testUserId = 2;
        $userPath = "users/{$testUserId}/files";
        
        // Ensure user directory exists
        $disk->makeDirectory($userPath);
        
        // Generate multiple test files with different content
        $testFiles = [
            'document1.txt' => 'Document content 1',
            'document2.txt' => 'Document content 2',
            'subfolder/document3.txt' => 'Document content 3',
        ];
        
        // Property: All file creations should use LaCie drive
        foreach ($testFiles as $fileName => $content) {
            $filePath = "{$userPath}/{$fileName}";
            
            // Create subdirectory if needed
            if (str_contains($fileName, '/')) {
                $disk->makeDirectory(dirname($filePath));
            }
            
            $result = $disk->put($filePath, $content);
            $this->assertTrue($result, "Failed to create file: {$fileName}");
            
            // Verify file exists on LaCie drive only
            $this->assertTrue($disk->exists($filePath));
            $this->assertFalse(Storage::disk('local')->exists($filePath));
            $this->assertFalse(Storage::disk('public')->exists($filePath));
        }
        
        // Property: File listing should show all files from LaCie drive
        $files = $disk->allFiles($userPath);
        $this->assertCount(3, $files);
        
        // Property: File operations should preserve content integrity
        foreach ($testFiles as $fileName => $expectedContent) {
            $filePath = "{$userPath}/{$fileName}";
            $actualContent = $disk->get($filePath);
            $this->assertEquals($expectedContent, $actualContent);
        }
    }

    /**
     * Property Test: Directory operations use LaCie drive
     * 
     * For any directory operation, verify it operates on LaCie drive
     */
    public function test_directory_operations_use_lacie_drive(): void
    {
        $disk = Storage::disk('lacie');
        $testUserId = 3;
        $userPath = "users/{$testUserId}/files";
        
        // Property: Directory creation should happen on LaCie drive
        $result = $disk->makeDirectory($userPath);
        $this->assertTrue($result);
        
        // Create nested directory structure
        $nestedPath = "{$userPath}/documents/projects/2024";
        $result = $disk->makeDirectory($nestedPath);
        $this->assertTrue($result);
        
        // Verify directories exist on LaCie drive only
        $this->assertTrue($disk->exists($userPath));
        $this->assertTrue($disk->exists($nestedPath));
        
        // Verify directories are NOT in Laravel storage
        $this->assertFalse(Storage::disk('local')->exists($userPath));
        $this->assertFalse(Storage::disk('public')->exists($userPath));
        
        // Property: Directory listing should work from LaCie drive
        $directories = $disk->directories($userPath);
        $this->assertContains("{$userPath}/documents", $directories);
    }

    /**
     * Property Test: File size calculations use LaCie drive
     * 
     * For any file size operation, verify it reads from LaCie drive
     */
    public function test_file_size_calculations_use_lacie_drive(): void
    {
        $disk = Storage::disk('lacie');
        $testUserId = 4;
        $userPath = "users/{$testUserId}/files";
        
        // Ensure user directory exists
        $disk->makeDirectory($userPath);
        
        // Create files with known sizes
        $testFiles = [
            'small.txt' => str_repeat('A', 100),    // 100 bytes
            'medium.txt' => str_repeat('B', 1000),  // 1000 bytes
            'large.txt' => str_repeat('C', 10000),  // 10000 bytes
        ];
        
        $totalExpectedSize = 0;
        
        foreach ($testFiles as $fileName => $content) {
            $filePath = "{$userPath}/{$fileName}";
            $disk->put($filePath, $content);
            
            // Property: File size should be calculated from LaCie drive
            $actualSize = $disk->size($filePath);
            $expectedSize = strlen($content);
            $this->assertEquals($expectedSize, $actualSize);
            
            $totalExpectedSize += $expectedSize;
        }
        
        // Property: Total directory size should sum all files on LaCie drive
        $allFiles = $disk->allFiles($userPath);
        $totalActualSize = 0;
        
        foreach ($allFiles as $file) {
            $totalActualSize += $disk->size($file);
        }
        
        $this->assertEquals($totalExpectedSize, $totalActualSize);
    }

    /**
     * Property Test: File streaming serves from LaCie drive
     * 
     * For any file download, verify it streams from LaCie drive
     */
    public function test_file_streaming_serves_from_lacie_drive(): void
    {
        $disk = Storage::disk('lacie');
        $testUserId = 5;
        $userPath = "users/{$testUserId}/files";
        
        // Ensure user directory exists
        $disk->makeDirectory($userPath);
        
        // Create test file with specific content
        $fileName = 'stream_test.txt';
        $filePath = "{$userPath}/{$fileName}";
        $testContent = 'This content should be streamed directly from LaCie drive';
        
        $disk->put($filePath, $testContent);
        
        // Property: File download should serve directly from LaCie drive
        $downloadedContent = $disk->get($filePath);
        $this->assertEquals($testContent, $downloadedContent);
        
        // Verify the file path resolves to LaCie drive location
        $fullPath = $disk->path($filePath);
        $this->assertStringContainsString('lacie', $fullPath);
        
        // Property: File should be readable as stream from LaCie drive
        $stream = $disk->readStream($filePath);
        $this->assertIsResource($stream);
        
        $streamContent = stream_get_contents($stream);
        fclose($stream);
        
        $this->assertEquals($testContent, $streamContent);
    }
}