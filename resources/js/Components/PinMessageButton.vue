<template>
    <button
        @click="togglePin"
        :class="[
            'p-1 rounded transition-colors',
            isPinned 
                ? 'text-yellow-500 dark:text-yellow-400 hover:text-yellow-700 dark:hover:text-yellow-300' 
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
        ]"
        :title="isPinned ? 'ピン留めを解除' : 'メッセージをピン留め'"
        :disabled="loading"
    >
        <svg 
            v-if="loading" 
            class="animate-spin h-4 w-4" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg 
            v-else 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke-width="1.5" 
            stroke="currentColor" 
            class="h-4 w-4"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
        </svg>
    </button>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    messageId: {
        type: [String, Number],
        required: true
    },
    channelId: {
        type: [String, Number],
        required: true
    },
    isPinned: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['pin-toggled']);

const loading = ref(false);

const togglePin = async () => {
    if (loading.value) return;
    
    loading.value = true;
    
    try {
        if (props.isPinned) {
            // ピン留めを解除 - メッセージIDとチャンネルIDを使用
            await axios.delete('/api/pinned-messages', {
                data: {
                    message_id: props.messageId,
                    channel_id: props.channelId
                }
            });
            emit('pin-toggled', false, null);
        } else {
            // ピン留めを追加
            const response = await axios.post('/api/pinned-messages', {
                message_id: props.messageId,
                channel_id: props.channelId
            });
            emit('pin-toggled', true, response.data.id);
        }
    } catch (error) {
        console.error('Pin toggle failed:', error);
        // エラーハンドリング（必要に応じてトースト通知など）
    } finally {
        loading.value = false;
    }
};
</script> 