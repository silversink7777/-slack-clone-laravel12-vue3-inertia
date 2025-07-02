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
        Schema::table('tbl_channel_invitations', function (Blueprint $table) {
            // 既存の外部キー制約を削除
            $table->dropForeign(['invitee_id']);
            
            // invitee_idをnullableに変更
            $table->foreignId('invitee_id')->nullable()->change();
            
            // 新しい外部キー制約を追加（nullable）
            $table->foreign('invitee_id')->references('id')->on('tbl_users')->onDelete('cascade');
            
            // ユニーク制約を更新（invitee_idがnullableの場合を考慮）
            $table->dropUnique('unique_channel_invitation');
            $table->unique(['channel_id', 'invitee_id', 'status'], 'unique_channel_invitation_user');
            $table->unique(['channel_id', 'invitee_email', 'status'], 'unique_channel_invitation_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_channel_invitations', function (Blueprint $table) {
            // ユニーク制約を削除
            $table->dropUnique('unique_channel_invitation_user');
            $table->dropUnique('unique_channel_invitation_email');
            
            // 外部キー制約を削除
            $table->dropForeign(['invitee_id']);
            
            // invitee_idをNOT NULLに戻す
            $table->foreignId('invitee_id')->nullable(false)->change();
            
            // 元の外部キー制約を復元
            $table->foreign('invitee_id')->references('id')->on('tbl_users')->onDelete('cascade');
            
            // 元のユニーク制約を復元
            $table->unique(['channel_id', 'invitee_id', 'status'], 'unique_channel_invitation');
        });
    }
};
