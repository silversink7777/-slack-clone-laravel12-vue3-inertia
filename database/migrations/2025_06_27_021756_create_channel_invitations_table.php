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
        Schema::create('tbl_channel_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('tbl_channels')->onDelete('cascade');
            $table->foreignId('inviter_id')->constrained('tbl_users')->onDelete('cascade');
            $table->foreignId('invitee_id')->constrained('tbl_users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            // インデックス
            $table->index(['channel_id', 'status']);
            $table->index(['invitee_id', 'status']);
            $table->unique(['channel_id', 'invitee_id', 'status'], 'unique_channel_invitation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_channel_invitations');
    }
};
