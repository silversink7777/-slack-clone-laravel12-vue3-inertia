<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { BellIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

// 招待数の状態管理
const invitationCount = ref(0);
let invitationCountInterval = null;

// 招待数を取得する関数
const fetchInvitationCount = async () => {
    try {
        const response = await axios.get('/my-invitations');
        if (response.data && Array.isArray(response.data)) {
            invitationCount.value = response.data.length;
        }
    } catch (error) {
        console.error('Failed to fetch invitation count:', error);
        invitationCount.value = 0;
    }
};

// 定期的に招待数を更新
const startInvitationCountInterval = () => {
    if (invitationCountInterval) clearInterval(invitationCountInterval);
    invitationCountInterval = setInterval(fetchInvitationCount, 30000); // 30秒ごとに更新
};

const navigateToInvitations = () => {
    router.visit('/invitations');
};

onMounted(() => {
    // 初期データを取得
    fetchInvitationCount();
    // 定期的な更新を開始
    startInvitationCountInterval();
    
    // 招待応答時のイベントをリッスン
    window.addEventListener('invitation-count-updated', fetchInvitationCount);
});

onUnmounted(() => {
    if (invitationCountInterval) clearInterval(invitationCountInterval);
    // イベントリスナーを削除
    window.removeEventListener('invitation-count-updated', fetchInvitationCount);
});
</script>

<template>
    <div class="w-20 bg-slack-purple-darker dark:bg-gray-900 text-white p-2">
        <!-- Workspace Icon -->
        <div class="rounded-lg bg-white text-purple-900 w-12 h-12 flex items-center justify-center text-2xl font-bold mb-4">
            T
        </div>

        <!-- 招待通知アイコン -->
        <button
            @click="navigateToInvitations"
            class="relative w-12 h-12 rounded-lg hover:bg-purple-700 flex items-center justify-center mb-2 transition-colors"
            title="招待通知"
        >
            <BellIcon class="h-6 w-6" />
            <!-- 通知数バッジ -->
            <div
                v-if="invitationCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center min-w-[20px]"
            >
                {{ invitationCount > 99 ? '99+' : invitationCount }}
            </div>
        </button>

        <!-- ダイレクトメッセージアイコン -->
        <button
            @click="() => router.visit('/direct-messages')"
            class="w-12 h-12 rounded-lg hover:bg-purple-700 flex items-center justify-center mb-2 transition-colors"
            title="ダイレクトメッセージ"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </button>
    </div>
</template> 