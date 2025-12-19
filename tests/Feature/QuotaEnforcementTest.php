<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $quotaService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with 10KB quota
        $this->user = User::create([
            'name' => 'Quota Test User',
            'email' => 'quota@example.com',
            'ad_username' => 'quotauser',
            'ad_guid' => '12345678-1234-1234-1234-123456789013',
            'display_name' => 'Quota Test User',
            'quota_bytes' => 10240, // 10KB
            'used_bytes' => 0,
        ]);

        $this->quotaService = app(QuotaService::class);

        // Set up session
        session(['user' => ['id' => $this->user->id]]);

        // Ensure test directory exists
        Storage::disk('lacie')->makeDirectory("users/{$this->user->id}/files");
    }

    /**
     * Property 6: Quota enforcement prevents overages
     * Validates: Requirements 4.4
     */
    public function test_quota_enforcement_prevents_single_file_overage()
    {
        // Try to upload file larger than quota
        $file = UploadedFile::fake()->create('oversized.txt', 15); // 15KB > 10KB quota
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertStringContainsString('quota', strtolower($results[0]['error']));

        // Verify no file was created
        $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/oversized.txt"));
        
        // Verify used bytes unchanged
        $this->user->refresh();
        $this->assertEquals(0, $this->user->used_bytes);
    }

    public function test_quota_enforcement_prevents_cumulative_overage()
    {
        // Upload file that uses most of quota
        $file1 = UploadedFile::fake()->create('first.txt', 8); // 8KB
        
        $response1 = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file1]
        ]);

        $response1->assertStatus(200);
        $this->assertTrue($response1->json('success'));

        // Try to upload another file that would exceed quota
        $file2 = UploadedFile::fake()->create('second.txt', 4); // 4KB (8KB + 4KB > 10KB)
        
        $response2 = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file2]
        ]);

        $response2->assertStatus(200);
        $results = $response2->json('results');
        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertStringContainsString('quota', strtolower($results[0]['error']));

        // Verify only first file exists
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/first.txt"));
        $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/second.txt"));
    }

    public function test_quota_enforcement_allows_within_limit_uploads()
    {
        // Upload file within quota
        $file = UploadedFile::fake()->create('within_limit.txt', 5); // 5KB < 10KB quota
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);

        // Verify file exists
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/within_limit.txt"));
        
        // Verify used bytes updated
        $this->user->refresh();
        $this->assertEquals(5 * 1024, $this->user->used_bytes);
    }

    public function test_chunked_upload_quota_enforcement()
    {
        // Try chunked upload that exceeds quota
        $file = UploadedFile::fake()->create('chunk.tmp', 1);
        
        $response = $this->postJson('/api/upload/chunk', [
            'file' => $file,
            'chunk' => 0,
            'chunks' => 20, // 20 chunks of 1KB = 20KB > 10KB quota
            'name' => 'large_chunked.txt',
            'path' => '',
            'size' => 20 * 1024, // 20KB total
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertTrue($response->json('quota_exceeded'));
    }

    public function test_quota_calculation_accuracy()
    {
        // Upload multiple files and verify quota calculation
        $files = [
            UploadedFile::fake()->create('file1.txt', 2), // 2KB
            UploadedFile::fake()->create('file2.txt', 3), // 3KB
            UploadedFile::fake()->create('file3.txt', 1), // 1KB
        ];
        
        foreach ($files as $file) {
            $response = $this->postJson('/api/files/upload', [
                'path' => '',
                'files' => [$file]
            ]);
            
            $response->assertStatus(200);
            $this->assertTrue($response->json('success'));
        }

        // Verify total used bytes
        $this->user->refresh();
        $expectedUsed = 2 * 1024 + 3 * 1024 + 1 * 1024; // 6KB
        $this->assertEquals($expectedUsed, $this->user->used_bytes);

        // Verify quota info calculation
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        $this->assertEquals($expectedUsed, $quotaInfo['used_bytes']);
        $this->assertEquals(10240, $quotaInfo['total_bytes']);
        $this->assertEquals(round($expectedUsed / 10240 * 100, 2), $quotaInfo['usage_percentage']);
    }

    public function test_quota_enforcement_after_file_deletion()
    {
        // Upload file
        $file = UploadedFile::fake()->create('to_delete.txt', 5); // 5KB
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify quota used
        $this->user->refresh();
        $this->assertEquals(5 * 1024, $this->user->used_bytes);

        // Delete file
        $deleteResponse = $this->deleteJson('/api/files/delete', [
            'path' => 'to_delete.txt'
        ]);

        $deleteResponse->assertStatus(200);
        $this->assertTrue($deleteResponse->json('success'));

        // Verify quota freed up
        $this->user->refresh();
        $this->assertEquals(0, $this->user->used_bytes);

        // Should now be able to upload another file
        $file2 = UploadedFile::fake()->create('after_delete.txt', 5); // 5KB
        
        $response2 = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file2]
        ]);

        $response2->assertStatus(200);
        $this->assertTrue($response2->json('success'));
    }

    public function test_quota_warning_thresholds()
    {
        // Upload file to reach warning threshold (70%)
        $file = UploadedFile::fake()->create('warning_test.txt', 7); // 7KB (70% of 10KB)
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Refresh user to get updated quota
        $this->user->refresh();

        // Check quota info includes warning
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        $this->assertTrue($quotaInfo['is_approaching_limit']);
        $this->assertFalse($quotaInfo['has_exceeded']);
    }

    public function test_quota_exceeded_threshold()
    {
        // Set user to have used 95% of quota
        $this->user->update(['used_bytes' => 9728]); // 9.5KB (95% of 10KB)

        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        $this->assertTrue($quotaInfo['is_approaching_limit']);
        $this->assertTrue($quotaInfo['has_exceeded']); // Should be true at 95%
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}");
        parent::tearDown();
    }
}