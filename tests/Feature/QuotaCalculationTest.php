<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Services\QuotaService;
use App\Services\StorageService;
use App\Services\FileService;
use App\Models\User;

/**
 * Feature: web-file-manager, Property 7: Quota calculations match actual usage
 * 
 * Property: For any user directory, the calculated storage usage should equal 
 * the sum of actual file sizes in their User_Folder
 * 
 * Validates: Requirements 4.5
 */
class QuotaCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $quotaService;
    protected $storageService;
    protected $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test LaCie drive
        config(['filesystems.disks.lacie.root' => storage_path('testing/lacie')]);
        
        // Create test directory structure
        if (!file_exists(storage_path('testing/lacie'))) {
            mkdir(storage_path('testing/lacie'), 0755, true);
        }
        if (!file_exists(storage_path('testing/lacie/users'))) {
            mkdir(storage_path('testing/lacie/users'), 0755, true);
        }

        $this->storageService = new StorageService();
        $this->quotaService = new QuotaService($this->storageService);
        $securityService = new \App\Services\SecurityService();
        $this->fileService = new FileService($this->quotaService, $securityService);
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
     * Property Test: Quota calculations match actual file sizes
     * 
     * For any user with files, calculated usage should equal sum of actual file sizes
     */
    public function test_quota_calculations_match_actual_file_sizes(): void
    {
        // Create test users with different quota sizes
        $testUsers = [
            ['quota_gb' => 1, 'files' => ['small.txt' => 100, 'medium.txt' => 1000]],
            ['quota_gb' => 5, 'files' => ['large.txt' => 50000, 'doc.pdf' => 25000, 'image.jpg' => 75000]],
            ['quota_gb' => 10, 'files' => ['video.mp4' => 500000, 'archive.zip' => 250000]],
        ];

        foreach ($testUsers as $index => $userData) {
            $user = User::create([
                'name' => "Test User {$index}",
                'email' => "user{$index}@test.com",
                'ad_username' => "user{$index}",
                'quota_bytes' => $userData['quota_gb'] * 1024 * 1024 * 1024,
                'used_bytes' => 0,
            ]);

            // Ensure user directory exists
            $this->storageService->ensureUserDirectory($user);
            
            $disk = Storage::disk('lacie');
            $userPath = "users/{$user->id}/files";
            $expectedTotalSize = 0;

            // Create test files with known sizes
            foreach ($userData['files'] as $fileName => $fileSize) {
                $content = str_repeat('A', $fileSize);
                $disk->put("{$userPath}/{$fileName}", $content);
                $expectedTotalSize += $fileSize;
            }

            // Property: Calculated usage should match actual file sizes
            $actualUsage = $this->storageService->getUserStorageUsage($user);
            $this->assertEquals($expectedTotalSize, $actualUsage, 
                "Calculated usage doesn't match expected for user {$user->id}");

            // Property: Recalculated usage should be accurate
            $recalculatedUsage = $this->quotaService->recalculateUsage($user);
            $this->assertEquals($expectedTotalSize, $recalculatedUsage,
                "Recalculated usage doesn't match expected for user {$user->id}");

            // Property: User model should reflect accurate usage after recalculation
            $user->refresh();
            $this->assertEquals($expectedTotalSize, $user->used_bytes,
                "User model used_bytes doesn't match actual usage");
        }
    }

    /**
     * Property Test: Quota updates work correctly with file operations
     * 
     * For any file operation, quota should be updated accurately
     */
    public function test_quota_updates_work_correctly_with_file_operations(): void
    {
        $user = User::create([
            'name' => 'Quota Test User',
            'email' => 'quota@test.com',
            'ad_username' => 'quotauser',
            'quota_bytes' => 5 * 1024 * 1024 * 1024, // 5GB
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);
        $disk = Storage::disk('lacie');
        $userPath = "users/{$user->id}/files";

        // Property: Initial usage should be zero
        $this->assertEquals(0, $user->used_bytes);
        $actualUsage = $this->storageService->getUserStorageUsage($user);
        $this->assertEquals(0, $actualUsage);

        // Property: Adding files should increase usage
        $testFiles = [
            'file1.txt' => 1000,
            'file2.txt' => 2000,
            'file3.txt' => 3000,
        ];

        $expectedUsage = 0;
        foreach ($testFiles as $fileName => $fileSize) {
            $content = str_repeat('B', $fileSize);
            $disk->put("{$userPath}/{$fileName}", $content);
            
            // Update quota manually (simulating file upload)
            $this->quotaService->updateUsedBytes($user, $fileSize);
            $expectedUsage += $fileSize;
            
            // Property: Used bytes should match expected
            $user->refresh();
            $this->assertEquals($expectedUsage, $user->used_bytes);
        }

        // Property: Actual storage usage should match tracked usage
        $actualUsage = $this->storageService->getUserStorageUsage($user);
        $this->assertEquals($expectedUsage, $actualUsage);

        // Property: Deleting files should decrease usage
        $fileToDelete = 'file2.txt';
        $deletedFileSize = $testFiles[$fileToDelete];
        
        $disk->delete("{$userPath}/{$fileToDelete}");
        $this->quotaService->updateUsedBytes($user, -$deletedFileSize);
        $expectedUsage -= $deletedFileSize;
        
        $user->refresh();
        $this->assertEquals($expectedUsage, $user->used_bytes);
        
        // Verify actual usage matches
        $actualUsage = $this->storageService->getUserStorageUsage($user);
        $this->assertEquals($expectedUsage, $actualUsage);
    }

    /**
     * Property Test: Quota information calculations are accurate
     * 
     * For any user, quota info should correctly calculate percentages and limits
     */
    public function test_quota_information_calculations_are_accurate(): void
    {
        $testCases = [
            ['quota_gb' => 1, 'used_bytes' => 512 * 1024 * 1024, 'expected_percentage' => 50.0],
            ['quota_gb' => 2, 'used_bytes' => 1.5 * 1024 * 1024 * 1024, 'expected_percentage' => 75.0],
            ['quota_gb' => 5, 'used_bytes' => 4.5 * 1024 * 1024 * 1024, 'expected_percentage' => 90.0],
            ['quota_gb' => 10, 'used_bytes' => 1 * 1024 * 1024 * 1024, 'expected_percentage' => 10.0],
        ];

        foreach ($testCases as $index => $testCase) {
            $user = User::create([
                'name' => "Quota Info Test User {$index}",
                'email' => "quotainfo{$index}@test.com",
                'ad_username' => "quotainfo{$index}",
                'quota_bytes' => $testCase['quota_gb'] * 1024 * 1024 * 1024,
                'used_bytes' => $testCase['used_bytes'],
            ]);

            // Property: Quota info should calculate correct percentages
            $quotaInfo = $this->quotaService->getQuotaInfo($user);
            
            $this->assertEquals($testCase['expected_percentage'], $quotaInfo['usage_percentage'],
                "Usage percentage incorrect for user {$user->id}");
            
            // Property: Available bytes should be correct
            $expectedAvailable = max(0, $user->quota_bytes - $user->used_bytes);
            $this->assertEquals($expectedAvailable, $quotaInfo['available_bytes'],
                "Available bytes incorrect for user {$user->id}");
            
            // Property: Approaching limit flag should be accurate
            $expectedApproaching = $testCase['expected_percentage'] >= 70;
            $this->assertEquals($expectedApproaching, $quotaInfo['is_approaching_limit'],
                "Approaching limit flag incorrect for user {$user->id}");
            
            // Property: Exceeded flag should be accurate
            $expectedExceeded = $testCase['expected_percentage'] >= 95;
            $this->assertEquals($expectedExceeded, $quotaInfo['has_exceeded'],
                "Exceeded flag incorrect for user {$user->id}");
        }
    }

    /**
     * Property Test: Quota validation works correctly
     * 
     * For any upload attempt, quota validation should correctly allow or deny
     */
    public function test_quota_validation_works_correctly(): void
    {
        $user = User::create([
            'name' => 'Quota Validation User',
            'email' => 'validation@test.com',
            'ad_username' => 'validationuser',
            'quota_bytes' => 1024 * 1024, // 1MB quota
            'used_bytes' => 800 * 1024,   // 800KB used
        ]);

        // Property: Upload within quota should be allowed
        $smallUpload = 100 * 1024; // 100KB
        $this->assertTrue($this->quotaService->canUpload($user, $smallUpload),
            "Small upload within quota should be allowed");

        // Property: Upload that would exceed quota should be denied
        $largeUpload = 300 * 1024; // 300KB (would exceed 1MB total)
        $this->assertFalse($this->quotaService->canUpload($user, $largeUpload),
            "Large upload exceeding quota should be denied");

        // Property: Upload exactly to quota limit should be allowed
        $exactUpload = 224 * 1024; // Exactly to 1MB total
        $this->assertTrue($this->quotaService->canUpload($user, $exactUpload),
            "Upload to exact quota limit should be allowed");

        // Property: Unlimited quota (0) should always allow uploads
        $unlimitedUser = User::create([
            'name' => 'Unlimited User',
            'email' => 'unlimited@test.com',
            'ad_username' => 'unlimited',
            'quota_bytes' => 0, // Unlimited
            'used_bytes' => 5 * 1024 * 1024 * 1024, // 5GB used
        ]);

        $this->assertTrue($this->quotaService->canUpload($unlimitedUser, 1024 * 1024 * 1024),
            "Unlimited quota should always allow uploads");
    }

    /**
     * Property Test: Quota statistics are calculated correctly
     * 
     * For any set of users, quota statistics should be accurate
     */
    public function test_quota_statistics_are_calculated_correctly(): void
    {
        // Create users with known quotas and usage
        $users = [
            ['quota_gb' => 5, 'used_gb' => 2],
            ['quota_gb' => 10, 'used_gb' => 8],
            ['quota_gb' => 2, 'used_gb' => 1],
            ['quota_gb' => 0, 'used_gb' => 0], // Unlimited user (should be excluded)
        ];

        $expectedTotalQuota = 0;
        $expectedTotalUsed = 0;
        $quotaUsers = 0;

        foreach ($users as $index => $userData) {
            $quotaBytes = $userData['quota_gb'] * 1024 * 1024 * 1024;
            $usedBytes = $userData['used_gb'] * 1024 * 1024 * 1024;

            User::create([
                'name' => "Stats User {$index}",
                'email' => "stats{$index}@test.com",
                'ad_username' => "stats{$index}",
                'quota_bytes' => $quotaBytes,
                'used_bytes' => $usedBytes,
            ]);

            if ($quotaBytes > 0) { // Only count users with quotas
                $expectedTotalQuota += $quotaBytes;
                $expectedTotalUsed += $usedBytes;
                $quotaUsers++;
            }
        }

        // Property: Statistics should match expected values
        $stats = $this->quotaService->getQuotaStatistics();
        
        $this->assertEquals($quotaUsers, $stats['total_users'],
            "Total users count incorrect");
        
        $this->assertEquals($expectedTotalQuota, $stats['total_quota'],
            "Total quota incorrect");
        
        $this->assertEquals($expectedTotalUsed, $stats['total_used'],
            "Total used incorrect");
        
        $expectedAvailable = $expectedTotalQuota - $expectedTotalUsed;
        $this->assertEquals($expectedAvailable, $stats['total_available'],
            "Total available incorrect");
        
        $expectedAverageUsage = $quotaUsers > 0 ? $expectedTotalUsed / $quotaUsers : 0;
        $this->assertEquals($expectedAverageUsage, $stats['average_usage'],
            "Average usage incorrect");
        
        $expectedOverallPercentage = $expectedTotalQuota > 0 ? 
            round(($expectedTotalUsed / $expectedTotalQuota) * 100, 2) : 0;
        $this->assertEquals($expectedOverallPercentage, $stats['overall_usage_percentage'],
            "Overall usage percentage incorrect");
    }

    /**
     * Property Test: Directory size calculation is accurate
     * 
     * For any directory structure, calculated size should match sum of file sizes
     */
    public function test_directory_size_calculation_is_accurate(): void
    {
        $user = User::create([
            'name' => 'Directory Size User',
            'email' => 'dirsize@test.com',
            'ad_username' => 'dirsizeuser',
            'quota_bytes' => 10 * 1024 * 1024 * 1024,
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);
        $disk = Storage::disk('lacie');
        $userPath = "users/{$user->id}/files";

        // Create nested directory structure with files
        $fileStructure = [
            'root_file.txt' => 1000,
            'documents/doc1.pdf' => 2000,
            'documents/doc2.txt' => 1500,
            'images/photo1.jpg' => 5000,
            'images/photo2.png' => 3000,
            'images/thumbnails/thumb1.jpg' => 500,
            'images/thumbnails/thumb2.jpg' => 750,
        ];

        $expectedTotalSize = 0;
        foreach ($fileStructure as $filePath => $fileSize) {
            $content = str_repeat('X', $fileSize);
            $disk->put("{$userPath}/{$filePath}", $content);
            $expectedTotalSize += $fileSize;
        }

        // Property: Directory size should equal sum of all file sizes
        $calculatedSize = $this->storageService->getDirectorySize($userPath);
        $this->assertEquals($expectedTotalSize, $calculatedSize,
            "Directory size calculation doesn't match expected total");

        // Property: User storage usage should match directory size
        $userUsage = $this->storageService->getUserStorageUsage($user);
        $this->assertEquals($expectedTotalSize, $userUsage,
            "User storage usage doesn't match directory size");

        // Property: Subdirectory sizes should be accurate
        $documentsSize = $this->storageService->getDirectorySize("{$userPath}/documents");
        $expectedDocumentsSize = 2000 + 1500; // doc1.pdf + doc2.txt
        $this->assertEquals($expectedDocumentsSize, $documentsSize,
            "Subdirectory size calculation incorrect");

        $imagesSize = $this->storageService->getDirectorySize("{$userPath}/images");
        $expectedImagesSize = 5000 + 3000 + 500 + 750; // All image files including thumbnails
        $this->assertEquals($expectedImagesSize, $imagesSize,
            "Images directory size calculation incorrect");
    }
}