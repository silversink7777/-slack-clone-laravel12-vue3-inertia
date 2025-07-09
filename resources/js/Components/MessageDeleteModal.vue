<script setup>
import { ref } from 'vue';
import { XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    message: Object,
    activeDirectMessage: Object,
});

const emit = defineEmits(['close', 'messageDeleted']);
const isDeleting = ref(false);
const errorMessage = ref('');

const handleDelete = async () => {
    if (!props.message) return;
    isDeleting.value = true;
    errorMessage.value = '';
    try {
        if (props.activeDirectMessage) {
            // DMメッセージの場合
            await axios.delete(`/api/direct-messages/${props.message.id}`);
        } else {
            // チャンネルメッセージの場合
            await axios.delete(`/messages/${props.message.id}`);
        }
        emit('messageDeleted', props.message.id);
        emit('close');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || '削除に失敗しました';
    } finally {
        isDeleting.value = false;
    }
};

const handleClose = () => {
    errorMessage.value = '';
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">メッセージを削除</h3>
                <button @click="handleClose" class="text-gray-500 hover:text-gray-700">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>
            <div class="mb-4 text-gray-700">
                本当にこのメッセージを削除しますか？
                <div class="mt-2 p-2 bg-gray-100 rounded text-gray-800">
                    {{ message?.content }}
                </div>
                <div v-if="errorMessage" class="mt-2 text-red-600 text-sm">
                    {{ errorMessage }}
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button @click="handleClose" class="px-4 py-2 text-gray-600 hover:text-gray-800" :disabled="isDeleting">
                    キャンセル
                </button>
                <button
                    @click="handleDelete"
                    :disabled="isDeleting"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                    <TrashIcon class="h-4 w-4" />
                    <span>{{ isDeleting ? '削除中...' : '削除' }}</span>
                </button>
            </div>
        </div>
    </div>
</template> 