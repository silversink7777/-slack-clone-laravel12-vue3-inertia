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
        Schema::create('tbl_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('tbl_channels')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_messages');
    }
};
