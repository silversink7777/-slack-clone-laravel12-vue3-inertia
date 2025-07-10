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
        Schema::create('tbl_pinned_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('tbl_messages')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('tbl_channels')->cascadeOnDelete();
            $table->foreignId('pinned_by')->constrained('tbl_users')->cascadeOnDelete();
            $table->timestamps();
            
            // 同じメッセージが同じチャンネルで重複してピン留めされることを防ぐ
            $table->unique(['message_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pinned_messages');
    }
};
