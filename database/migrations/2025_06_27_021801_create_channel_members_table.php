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
        Schema::create('tbl_channel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('tbl_channels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('tbl_users')->onDelete('cascade');
            $table->enum('role', ['member', 'admin'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // インデックス
            $table->index(['channel_id', 'role']);
            $table->index(['user_id']);
            $table->unique(['channel_id', 'user_id'], 'unique_channel_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_channel_members');
    }
};
