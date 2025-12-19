<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't already exist before adding them
            if (!Schema::hasColumn('users', 'ad_username')) {
                $table->string('ad_username')->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'ad_guid')) {
                $table->string('ad_guid')->unique()->nullable()->after('ad_username');
            }
            if (!Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name')->nullable()->after('ad_guid');
            }
            if (!Schema::hasColumn('users', 'quota_bytes')) {
                $table->bigInteger('quota_bytes')->default(0)->after('display_name');
            }
            if (!Schema::hasColumn('users', 'used_bytes')) {
                $table->bigInteger('used_bytes')->default(0)->after('quota_bytes');
            }
            if (!Schema::hasColumn('users', 'last_login')) {
                $table->timestamp('last_login')->nullable()->after('used_bytes');
            }
            
            // Make password nullable for AD users
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ad_username',
                'ad_guid', 
                'display_name',
                'quota_bytes',
                'used_bytes',
                'last_login'
            ]);
            
            // Revert password to not nullable
            $table->string('password')->nullable(false)->change();
        });
    }
};
