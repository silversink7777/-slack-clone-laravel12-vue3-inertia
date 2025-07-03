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
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->text('bio')->nullable()->after('email');
            $table->string('location')->nullable()->after('bio');
            $table->string('website')->nullable()->after('location');
            $table->string('phone')->nullable()->after('website');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('timezone')->default('UTC')->after('birth_date');
            $table->string('language')->default('en')->after('timezone');
            $table->json('social_links')->nullable()->after('language');
            $table->boolean('is_public_profile')->default(true)->after('social_links');
            $table->timestamp('last_seen_at')->nullable()->after('is_public_profile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'bio',
                'location',
                'website',
                'phone',
                'birth_date',
                'timezone',
                'language',
                'social_links',
                'is_public_profile',
                'last_seen_at'
            ]);
        });
    }
};
