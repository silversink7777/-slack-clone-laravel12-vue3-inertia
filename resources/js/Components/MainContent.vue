<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { PencilIcon } from '@heroicons/vue/24/outline';
import MainHeader from '@/Components/MainHeader.vue';
import MessageInput from '@/Components/MessageInput.vue';
import MessageEditModal from '@/Components/MessageEditModal.vue';
import MessageDeleteModal from '@/Components/MessageDeleteModal.vue';

const page = usePage();
const currentUser = page.props.auth?.user;

const props = defineProps({
    messages: {
        type: Array,
        default: () => []
    },
    activeChannel: Object,
});

const emit = defineEmits(['messageSent', 'messageUpdated', 'messageDeleted']);

const localMessages = ref([...(props.messages || [])]);
const showEditModal = ref(false);
const editingMessage = ref(null);
const showDeleteModal = ref(false);
const deletingMessage = ref(null);

// messagesプロパティの変更を監視してローカルリストを更新
watch(() => props.messages, (newMessages) => {
    localMessages.value = [...(newMessages || [])];
}, { deep: true });

const handleMessageSent = (newMessage) => {
    // ローカルのメッセージリストに追加
    localMessages.value.unshift(newMessage);
    
    // 親コンポーネントに通知
    emit('messageSent', newMessage);
};

const handleMessageUpdated = (updatedMessage) => {
    // ローカルのメッセージリストを更新
    const index = localMessages.value.findIndex(msg => msg.id === updatedMessage.id);
    if (index !== -1) {
        localMessages.value[index] = updatedMessage;
    }
    
    // 親コンポーネントに通知
    emit('messageUpdated', updatedMessage);
};

const handleMessageDeleted = (deletedId) => {
    const idx = localMessages.value.findIndex(msg => msg.id === deletedId);
    if (idx !== -1) localMessages.value.splice(idx, 1);
    emit('messageDeleted', deletedId);
};

const openEditModal = (message) => {
    editingMessage.value = message;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingMessage.value = null;
};

const openDeleteModal = (message) => {
    deletingMessage.value = message;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingMessage.value = null;
};

const isOwnMessage = (message) => {
    return currentUser && message.user.id === currentUser.id;
};
</script>

<template>
    <div class="flex-1 flex flex-col bg-white">
        <MainHeader :active-channel="activeChannel" />

        <!-- Messages Area -->
        <div class="flex-1 p-6 overflow-y-auto">
            <!-- Messages will be displayed here -->
            <div
                v-for="message in localMessages"
                :key="message.id"
                class="flex items-start mb-4 group hover:bg-gray-50 rounded-lg p-2 -m-2"
            >
                <div class="w-10 h-10 rounded bg-purple-400 mr-4 shrink-0"></div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <p class="font-bold">
                            {{ message.user.name }}
                            <span class="text-xs text-gray-500 font-normal ml-2">{{ message.time }}</span>
                        </p>
                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                v-if="isOwnMessage(message)"
                                @click="openEditModal(message)"
                                class="text-gray-500 hover:text-gray-700 p-1 rounded"
                                title="メッセージを編集"
                            >
                                <PencilIcon class="h-4 w-4" />
                            </button>
                            <button
                                v-if="isOwnMessage(message)"
                                @click="openDeleteModal(message)"
                                class="text-red-500 hover:text-red-700 p-1 rounded"
                                title="メッセージを削除"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-gray-800">{{ message.content }}</p>
                </div>
            </div>
        </div>

        <MessageInput 
            :active-channel="activeChannel"
            @message-sent="handleMessageSent"
        />

        <!-- Message Edit Modal -->
        <MessageEditModal
            :show="showEditModal"
            :message="editingMessage"
            @close="closeEditModal"
            @message-updated="handleMessageUpdated"
        />

        <!-- Message Delete Modal -->
        <MessageDeleteModal
            :show="showDeleteModal"
            :message="deletingMessage"
            @close="closeDeleteModal"
            @message-deleted="handleMessageDeleted"
        />
    </div>
</template> 