<script setup>
import { ref } from 'vue';
import {
    PlusIcon,
    FaceSmileIcon,
    AtSymbolIcon,
    VideoCameraIcon,
    MicrophoneIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    activeChannel: Object,
});

const emit = defineEmits(['messageSent']);

const messageContent = ref('');
const isSending = ref(false);

const sendMessage = async () => {
    if (!messageContent.value.trim() || !props.activeChannel) {
        return;
    }

    isSending.value = true;

    try {
        const response = await axios.post('/messages', {
            content: messageContent.value.trim(),
            channel_id: props.activeChannel.id,
        });

        // メッセージ送信成功
        emit('messageSent', response.data);
        messageContent.value = '';
    } catch (error) {
        console.error('Message sending failed:', error);
        // TODO: エラーメッセージをユーザーに表示
    } finally {
        isSending.value = false;
    }
};

const handleKeyPress = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};
</script>

<template>
    <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2 flex flex-col">
            <textarea
                v-model="messageContent"
                @keypress="handleKeyPress"
                class="w-full border-none focus:ring-0 resize-none bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                placeholder="メモを書く"
                rows="1"
                :disabled="isSending"
            ></textarea>

            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center space-x-2">
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><PlusIcon class="h-6 w-6" /></button>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><VideoCameraIcon class="h-6 w-6" /></button>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><MicrophoneIcon class="h-6 w-6" /></button>
                </div>
                <div class="flex items-center space-x-2">
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><FaceSmileIcon class="h-6 w-6" /></button>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><AtSymbolIcon class="h-6 w-6" /></button>
                    <button 
                        @click="sendMessage"
                        :disabled="!messageContent.trim() || isSending"
                        class="bg-gray-800 text-white rounded p-1 hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        <PaperAirplaneIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template> 