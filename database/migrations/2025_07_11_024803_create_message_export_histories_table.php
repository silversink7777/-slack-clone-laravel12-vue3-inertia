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
        Schema::create('tbl_message_export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tbl_users')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('tbl_channels')->onDelete('cascade');
            $table->foreignId('partner_id')->nullable()->constrained('tbl_users')->onDelete('cascade');
            $table->enum('format', ['csv', 'json']);
            $table->string('filename');
            $table->integer('message_count')->default(0);
            $table->bigInteger('file_size')->nullable();
            $table->timestamp('exported_at')->useCurrent();
            $table->timestamps();

            // インデックス
            $table->index(['user_id', 'created_at']);
            $table->index(['channel_id', 'created_at']);
            $table->index(['partner_id', 'created_at']);
            $table->index('filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_message_export_histories');
    }
};
