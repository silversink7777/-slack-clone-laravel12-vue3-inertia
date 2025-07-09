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
        Schema::create('tbl_direct_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->text('content');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // インデックスを追加
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'sender_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_direct_messages');
    }
};
