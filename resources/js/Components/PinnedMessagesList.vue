<template>
    <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 mr-2 text-yellow-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                    </svg>
                    ピン留めされたメッセージ
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ pinnedMessages.length }})</span>
                </h3>
                <button
                    v-if="pinnedMessages.length > 0"
                    @click="toggleExpanded"
                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    {{ isExpanded ? '折りたたむ' : '展開' }}
                </button>
            </div>
            
            <div v-if="loading" class="flex justify-center py-4">
                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <div v-else-if="pinnedMessages.length === 0" class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">ピン留めされたメッセージはありません</p>
            </div>
            
            <div v-else-if="isExpanded" class="space-y-3 max-h-96 overflow-y-auto">
                <div
                    v-for="pinnedMessage in pinnedMessages"
                    :key="pinnedMessage.id"
                    class="bg-white dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
                >
                    <div class="flex items-start space-x-3">
                        <img 
                            :src="pinnedMessage.message.user.avatar" 
                            :alt="pinnedMessage.message.user.name"
                            class="w-8 h-8 rounded-full flex-shrink-0"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ pinnedMessage.message.user.name }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ pinnedMessage.message.time }}
                                    </span>
                                </div>
                                <button
                                    @click="removePin(pinnedMessage.id)"
                                    class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 p-1"
                                    title="ピン留めを解除"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                {{ pinnedMessage.message.content }}
                            </p>
                            <!-- ファイル添付表示 -->
                            <div v-if="pinnedMessage.message.file_path" class="mt-2">
                                <img v-if="pinnedMessage.message.file_mime && pinnedMessage.message.file_mime.startsWith('image/')"
                                     :src="`/storage/${pinnedMessage.message.file_path}`"
                                     :alt="pinnedMessage.message.file_name"
                                     class="max-h-24 rounded border"
                                />
                                <div v-else class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                    </svg>
                                    <span>{{ pinnedMessage.message.file_name }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                {{ pinnedMessage.pinned_by.name }} がピン留めしました
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    channelId: {
        type: [String, Number],
        required: true
    }
});

const emit = defineEmits(['pin-removed']);

const pinnedMessages = ref([]);
const loading = ref(false);
const isExpanded = ref(false);

const fetchPinnedMessages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/pinned-messages', {
            params: { channel_id: props.channelId }
        });
        pinnedMessages.value = response.data;
    } catch (error) {
        console.error('Failed to fetch pinned messages:', error);
    } finally {
        loading.value = false;
    }
};

const removePin = async (pinnedMessageId) => {
    try {
        await axios.delete(`/api/pinned-messages/${pinnedMessageId}`);
        // リストから削除
        const index = pinnedMessages.value.findIndex(pm => pm.id === pinnedMessageId);
        if (index !== -1) {
            pinnedMessages.value.splice(index, 1);
        }
        emit('pin-removed', pinnedMessageId);
    } catch (error) {
        console.error('Failed to remove pin:', error);
    }
};

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

// 親コンポーネントから呼び出せるようにメソッドを公開
const refreshPinnedMessages = () => {
    fetchPinnedMessages();
};

// 親コンポーネントから呼び出せるようにメソッドを公開
defineExpose({
    refreshPinnedMessages
});

onMounted(() => {
    fetchPinnedMessages();
});
</script> 