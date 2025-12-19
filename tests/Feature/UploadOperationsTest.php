<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\FileService;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $fileService;
    protected $quotaService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'ad_username' => 'testuser',
            'ad_guid' => '12345678-1234-1234-1234-123456789012',
            'display_name' => 'Test User',
            'quota_bytes' => 1024 * 1024 * 100, // 100MB
            'used_bytes' => 0,
        ]);

        $this->fileService = app(FileService::class);
        $this->quotaService = app(QuotaService::class);

        // Set up session
        session(['user' => ['id' => $this->user->id]]);

        // Ensure test directory exists
        Storage::disk('lacie')->makeDirectory("users/{$this->user->id}/files");
        
        // Clean up any existing temp upload directories
        $tempDir = storage_path('app/temp/uploads');
        if (is_dir($tempDir)) {
            $this->recursiveRemoveDirectory($tempDir);
        }
    }

    /**
     * Property 3: Upload operations maintain integrity
     * Validates: Requirements 2.2, 2.3, 2.4, 2.5
     */
    public function test_upload_operations_maintain_integrity()
    {
        // Create a test file with exact content
        $content = str_repeat('A', 1024); // Exactly 1024 bytes
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        
        $file = new UploadedFile($tempPath, 'test.txt', 'text/plain', null, true);
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('test.txt', $results[0]['name']);

        // Verify file exists on disk
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/test.txt"));
        
        // Verify file size matches
        $diskSize = Storage::disk('lacie')->size("users/{$this->user->id}/files/test.txt");
        $this->assertEquals(1024, $diskSize);
        
        fclose($tempFile);
    }

    public function test_chunked_upload_maintains_integrity()
    {
        // Create a test file content
        $content = str_repeat('A', 2048); // 2KB file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, $content);

        $fileName = 'chunked_test.txt';
        $fileSize = strlen($content);
        $chunkSize = 1024; // 1KB chunks
        $chunks = ceil($fileSize / $chunkSize);

        // Upload chunks
        $chunkTempFiles = [];
        $lastResponse = null;
        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $fileSize);
            $chunkContent = substr($content, $start, $end - $start);
            
            $isFinal = (int)$chunk === (int)($chunks - 1);
            
            $chunkTempFile = tmpfile();
            fwrite($chunkTempFile, $chunkContent);
            $chunkTempPath = stream_get_meta_data($chunkTempFile)['uri'];
            $chunkFile = new UploadedFile($chunkTempPath, 'chunk.tmp', 'application/octet-stream', null, true);
            $chunkTempFiles[] = $chunkTempFile; // Keep reference to close later
            
            $response = $this->postJson('/api/upload/chunk', [
                'file' => $chunkFile,
                'chunk' => $chunk,
                'chunks' => $chunks,
                'name' => $fileName,
                'path' => '',
                'size' => $fileSize,
            ]);

            if ($response->status() !== 200) {
                $this->fail("Chunk {$chunk} failed with status {$response->status()}: " . $response->getContent());
            }
            $response->assertStatus(200);
            $this->assertTrue($response->json('success'));
            
            $lastResponse = $response;
            
            if ($isFinal) {
                // Last chunk should complete the upload
                if (!$response->json('success')) {
                    $this->fail("Last chunk failed: " . $response->json('error'));
                }
                $this->assertTrue($response->json('completed'));
            } else {
                // Non-final chunks should not be completed
                if ($response->json('completed')) {
                    $this->fail("Chunk {$chunk} (non-final) returned completed=true. Response: " . json_encode($response->json()));
                }
                $this->assertFalse($response->json('completed'));
            }
        }
        
        // Close chunk temp files
        foreach ($chunkTempFiles as $chunkTempFile) {
            fclose($chunkTempFile);
        }

        // Verify final file exists and has correct content
        // Verify final file exists and has correct content
        $filePath = "users/{$this->user->id}/files/{$fileName}";
        $this->assertTrue(Storage::disk('lacie')->exists($filePath));
        $uploadedContent = Storage::disk('lacie')->get($filePath);
        $this->assertEquals($content, $uploadedContent);

        unlink($tempFile);
    }

    public function test_upload_progress_tracking()
    {
        // This test would verify progress tracking functionality
        // For now, we'll test the basic structure
        
        $file = UploadedFile::fake()->create('progress_test.txt', 1024);
        
        $response = $this->postJson('/api/upload/chunk', [
            'file' => $file,
            'chunk' => 0,
            'chunks' => 2,
            'name' => 'progress_test.txt',
            'path' => '',
            'size' => 2048,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertFalse($response->json('completed'));
        $this->assertArrayHasKey('progress', $response->json());
    }

    public function test_upload_error_handling()
    {
        // Test invalid file upload
        $response = $this->postJson('/api/upload/chunk', [
            'chunk' => 0,
            'chunks' => 1,
            'name' => 'test.txt',
            'path' => '',
            'size' => 1024,
            // Missing 'file' parameter
        ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_upload_with_duplicate_names()
    {
        // Create first file
        $content1 = str_repeat('A', 512);
        $tempFile1 = tmpfile();
        fwrite($tempFile1, $content1);
        $tempPath1 = stream_get_meta_data($tempFile1)['uri'];
        $file1 = new UploadedFile($tempPath1, 'duplicate.txt', 'text/plain', null, true);
        
        $response1 = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file1]
        ]);

        $response1->assertStatus(200);
        $this->assertTrue($response1->json('success'));

        // Create second file with same name
        $content2 = str_repeat('B', 512);
        $tempFile2 = tmpfile();
        fwrite($tempFile2, $content2);
        $tempPath2 = stream_get_meta_data($tempFile2)['uri'];
        $file2 = new UploadedFile($tempPath2, 'duplicate.txt', 'text/plain', null, true);
        
        $response2 = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file2]
        ]);

        $response2->assertStatus(200);
        $this->assertTrue($response2->json('success'));
        
        $results = $response2->json('results');
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        
        // Should have unique name like "duplicate (1).txt"
        $this->assertStringContainsString('duplicate', $results[0]['name']);
        $this->assertNotEquals('duplicate.txt', $results[0]['name']);
        
        fclose($tempFile1);
        fclose($tempFile2);
    }

    /**
     * Property 6: Quota enforcement prevents overages
     * Validates: Requirements 4.4
     */
    public function test_quota_enforcement_prevents_overages()
    {
        // Set user quota to 1KB
        $this->user->update(['quota_bytes' => 1024]);

        // Create a 2KB file (should fail)
        $content = str_repeat('A', 2048);
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $file = new UploadedFile($tempPath, 'large.txt', 'text/plain', null, true);
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertStringContainsString('quota', strtolower($results[0]['error']));
        
        fclose($tempFile);
    }

    public function test_chunked_upload_quota_enforcement()
    {
        // Set user quota to 1KB
        $this->user->update(['quota_bytes' => 1024]);

        // Try to upload 2KB file via chunks (should fail on first chunk)
        $file = UploadedFile::fake()->create('chunk.tmp', 1024);
        
        $response = $this->postJson('/api/upload/chunk', [
            'file' => $file,
            'chunk' => 0,
            'chunks' => 2,
            'name' => 'large_chunked.txt',
            'path' => '',
            'size' => 2048, // Total size exceeds quota
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertTrue($response->json('quota_exceeded'));
    }

    public function test_quota_updates_after_upload()
    {
        $initialUsed = $this->user->used_bytes;
        
        // Create a file with exact size
        $content = str_repeat('A', 1024);
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $file = new UploadedFile($tempPath, 'quota_test.txt', 'text/plain', null, true);
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Refresh user and check quota update
        $this->user->refresh();
        $this->assertEquals($initialUsed + 1024, $this->user->used_bytes);
        
        fclose($tempFile);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}");
        
        // Clean up temp upload directories
        $tempDir = storage_path('app/temp/uploads');
        if (is_dir($tempDir)) {
            $this->recursiveRemoveDirectory($tempDir);
        }
        
        parent::tearDown();
    }
    
    private function recursiveRemoveDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        try {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    $this->recursiveRemoveDirectory($path);
                } else {
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors in tests
        }
    }
}