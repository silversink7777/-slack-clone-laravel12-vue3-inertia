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
    </div>
</template> 