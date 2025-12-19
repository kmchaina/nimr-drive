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
        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('ad_username');
            $table->index('ad_guid');
            $table->index('email');
        });

        // Add indexes to shares table
        Schema::table('shares', function (Blueprint $table) {
            $table->index('owner_id');
            $table->index('shared_with_user_id');
            $table->index(['owner_id', 'file_path']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['ad_username']);
            $table->dropIndex(['ad_guid']);
            $table->dropIndex(['email']);
        });

        // Remove indexes from shares table
        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['shared_with_user_id']);
            $table->dropIndex(['owner_id', 'file_path']);
            $table->dropIndex(['created_at']);
        });
    }
};
