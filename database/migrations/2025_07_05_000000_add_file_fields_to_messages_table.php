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
        Schema::table('tbl_messages', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('content');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_mime')->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_messages', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_mime', 'file_size']);
        });
    }
}; 