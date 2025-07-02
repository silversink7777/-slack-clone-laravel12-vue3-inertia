<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['createChannel', 'close']);

const newChannelName = ref('');

const createChannel = () => {
    if (newChannelName.value.trim()) {
        emit('createChannel', newChannelName.value.trim());
        newChannelName.value = '';
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center" @click.self="closeModal">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-bold mb-4">新しいチャンネルを作成する</h2>
            <form @submit.prevent="createChannel">
                <div>
                    <label for="channel-name" class="block text-sm font-medium text-gray-700">チャンネル名</label>
                    <input
                        v-model="newChannelName"
                        type="text"
                        id="channel-name"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="# e.g. 新規プロジェクト"
                        required
                    >
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                    >
                        キャンセル
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slack-purple-dark text-white rounded-md hover:bg-slack-purple-darker"
                    >
                        作成する
                    </button>
                </div>
            </form>
        </div>
    </div>
</template> 