<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuotaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
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
            'quota_bytes' => 1024 * 10, // 10KB quota for testing
            'used_bytes' => 0,
        ]);

        $this->quotaService = app(QuotaService::class);

        // Set up session
        session(['user' => ['id' => $this->user->id]]);

        // Ensure test directory exists
        Storage::disk('lacie')->makeDirectory("users/{$this->user->id}/files");
    }

    /**
     * Test quota display and visual warnings
     */
    public function test_quota_visual_warnings_display_correctly()
    {
        // Test normal usage (under 70%)
        $this->user->update(['used_bytes' => 5 * 1024]); // 5KB used of 10KB
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        
        $this->assertEquals(50.0, $quotaInfo['usage_percentage']);
        $this->assertFalse($quotaInfo['is_approaching_limit']);
        $this->assertFalse($quotaInfo['has_exceeded']);
        
        // Test approaching limit (over 80%)
        $this->user->update(['used_bytes' => 9 * 1024]); // 9KB used of 10KB
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        
        $this->assertEquals(90.0, $quotaInfo['usage_percentage']);
        $this->assertTrue($quotaInfo['is_approaching_limit']);
        $this->assertFalse($quotaInfo['has_exceeded']);
        
        // Test exceeded quota
        $this->user->update(['used_bytes' => 11 * 1024]); // 11KB used of 10KB
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        
        $this->assertEquals(110.0, $quotaInfo['usage_percentage']);
        $this->assertTrue($quotaInfo['is_approaching_limit']);
        $this->assertTrue($quotaInfo['has_exceeded']);
    }

    /**
     * Test real-time quota updates via API
     */
    public function test_quota_api_endpoint_returns_current_info()
    {
        // Update user quota
        $this->user->update(['used_bytes' => 3 * 1024]); // 3KB used
        
        $response = $this->getJson('/api/quota');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'quota' => [
                'used_bytes' => 3 * 1024,
                'total_bytes' => 10 * 1024,
                'usage_percentage' => 30.0,
                'is_approaching_limit' => false,
                'has_exceeded' => false,
            ]
        ]);
    }

    /**
     * Test quota recalculation functionality
     */
    public function test_quota_recalculation_updates_from_filesystem()
    {
        // Create a test file directly on the filesystem
        $content = str_repeat('A', 2048); // 2KB file
        Storage::disk('lacie')->put("users/{$this->user->id}/files/test.txt", $content);
        
        // User's used_bytes should be 0 initially (not updated yet)
        $this->assertEquals(0, $this->user->used_bytes);
        
        // Recalculate quota via API
        $response = $this->postJson('/api/quota/recalculate');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quota recalculated successfully'
        ]);
        
        // Verify user's quota was updated
        $this->user->refresh();
        $this->assertEquals(2048, $this->user->used_bytes);
        
        // Verify response contains updated quota info
        $quotaData = $response->json('quota');
        $this->assertEquals(2048, $quotaData['used_bytes']);
        $this->assertEquals(20.0, $quotaData['usage_percentage']); // 2KB of 10KB
    }

    /**
     * Test quota updates after file operations
     */
    public function test_quota_updates_after_file_operations()
    {
        // Upload a file
        $content = str_repeat('A', 1024); // 1KB file
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $file = new UploadedFile($tempPath, 'test.txt', 'text/plain', null, true);
        
        $response = $this->postJson('/api/files/upload', [
            'path' => '',
            'files' => [$file]
        ]);
        
        $response->assertStatus(200);
        
        // Verify quota was updated
        $this->user->refresh();
        $this->assertEquals(1024, $this->user->used_bytes);
        
        // Delete the file
        $deleteResponse = $this->deleteJson('/api/files/delete', [
            'path' => 'test.txt'
        ]);
        
        $deleteResponse->assertStatus(200);
        
        // Verify quota was updated after deletion
        $this->user->refresh();
        $this->assertEquals(0, $this->user->used_bytes);
        
        fclose($tempFile);
    }

    /**
     * Test quota enforcement prevents uploads when exceeded
     */
    public function test_quota_enforcement_prevents_uploads_when_exceeded()
    {
        // Set user close to quota limit
        $this->user->update(['used_bytes' => 9.5 * 1024]); // 9.5KB used of 10KB
        
        // Try to upload a 1KB file (should exceed quota)
        $content = str_repeat('A', 1024); // 1KB file
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

    /**
     * Test quota formatting and display values
     */
    public function test_quota_formatting_displays_correctly()
    {
        // Test various quota sizes
        $testCases = [
            ['bytes' => 512, 'expected' => '512 B'],
            ['bytes' => 1024, 'expected' => '1 KB'],
            ['bytes' => 1536, 'expected' => '1.5 KB'],
            ['bytes' => 1024 * 1024, 'expected' => '1 MB'],
            ['bytes' => 1024 * 1024 * 1024, 'expected' => '1 GB'],
        ];
        
        foreach ($testCases as $case) {
            $this->user->update(['used_bytes' => $case['bytes']]);
            $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
            
            $this->assertEquals($case['expected'], $quotaInfo['used_formatted']);
        }
    }

    /**
     * Test unlimited quota handling
     */
    public function test_unlimited_quota_handling()
    {
        // Set unlimited quota (0 or negative value)
        $this->user->update(['quota_bytes' => 0]);
        
        $quotaInfo = $this->quotaService->getQuotaInfo($this->user);
        
        $this->assertTrue($quotaInfo['is_unlimited']);
        $this->assertEquals('Unlimited', $quotaInfo['total_formatted']);
        $this->assertEquals('Unlimited', $quotaInfo['available_formatted']);
        $this->assertFalse($quotaInfo['is_approaching_limit']);
        $this->assertFalse($quotaInfo['has_exceeded']);
        
        // Test that uploads are allowed with unlimited quota
        $this->assertTrue($this->quotaService->canUpload($this->user, 1024 * 1024 * 100)); // 100MB
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}");
        parent::tearDown();
    }
}