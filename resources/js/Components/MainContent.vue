<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { PencilIcon } from '@heroicons/vue/24/outline';
import MainHeader from '@/Components/MainHeader.vue';
import MessageInput from '@/Components/MessageInput.vue';
import MessageEditModal from '@/Components/MessageEditModal.vue';
import MessageDeleteModal from '@/Components/MessageDeleteModal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PinMessageButton from '@/Components/PinMessageButton.vue';
import PinnedMessagesList from '@/Components/PinnedMessagesList.vue';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const page = usePage();
const currentUser = page.props.auth?.user;

const props = defineProps({
    messages: {
        type: Array,
        default: () => []
    },
    activeChannel: Object,
    activeDirectMessage: Object,
});

const emit = defineEmits(['messageSent', 'messageUpdated', 'messageDeleted']);

const localMessages = ref([...(props.messages || [])]);
const showEditModal = ref(false);
const editingMessage = ref(null);
const showDeleteModal = ref(false);
const deletingMessage = ref(null);
const previewFile = ref(null);
const showPreviewModal = ref(false);
const messagePinStatus = ref({});
const pinnedMessageIds = ref({});
const pinnedMessagesListRef = ref(null);

const md = new MarkdownIt({ 
    html: false, 
    linkify: true, 
    breaks: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return ''; // デフォルトのエスケープ処理を使用
    }
});

// messagesプロパティの変更を監視してローカルリストを更新
watch(() => props.messages, (newMessages) => {
    localMessages.value = [...(newMessages || [])];
    
    // ピン留め状態を初期化
    if (newMessages) {
        newMessages.forEach(message => {
            if (message.is_pinned !== undefined) {
                messagePinStatus.value[message.id] = message.is_pinned;
            }
            if (message.pinned_message_id !== undefined) {
                pinnedMessageIds.value[message.id] = message.pinned_message_id;
            }
        });
    }
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

const handlePinToggled = (messageId, isPinned, pinnedMessageId = null) => {
    messagePinStatus.value[messageId] = isPinned;
    if (pinnedMessageId) {
        pinnedMessageIds.value[messageId] = pinnedMessageId;
    } else {
        delete pinnedMessageIds.value[messageId];
    }
    
    // ピン留め一覧を更新
    if (pinnedMessagesListRef.value) {
        pinnedMessagesListRef.value.refreshPinnedMessages();
    }
};

const handlePinRemoved = (pinnedMessageId) => {
    // ピン留め一覧から削除された場合、対応するメッセージのピン留め状態を更新
    // ピン留めレコードのIDからメッセージIDを特定する必要がある
    // この処理は、ピン留め一覧が更新された後に、メッセージ一覧も更新されるため、
    // 実際にはメッセージ一覧の再取得で対応される
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

const openPreview = (file) => {
    previewFile.value = file;
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewFile.value = null;
};

const isOwnMessage = (message) => {
    return currentUser && message.user.id === currentUser.id;
};
</script>

<template>
    <div class="flex-1 flex flex-col bg-white dark:bg-gray-900">
        <MainHeader :active-channel="activeChannel" :active-direct-message="activeDirectMessage" />

        <!-- ピン留めメッセージ一覧 -->
        <PinnedMessagesList 
            v-if="activeChannel" 
            :channel-id="activeChannel.id"
            @pin-removed="(pinnedMessageId) => handlePinRemoved(pinnedMessageId)"
            ref="pinnedMessagesListRef"
        />

        <!-- Messages Area -->
        <div class="flex-1 p-6 overflow-y-auto">
            <template v-if="activeChannel">
                <!-- チャンネルメッセージ表示 -->
                <div
                    v-for="message in localMessages"
                    :key="message.id"
                    class="flex items-start mb-4 group hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg p-2 -m-2"
                >
                    <div class="w-10 h-10 rounded bg-purple-400 mr-4 shrink-0">
                        <img :src="message.user.avatar" alt="avatar" class="w-8 h-8 rounded-full" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-gray-900 dark:text-gray-100">
                                {{ message.user.name }}
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">{{ message.time }}</span>
                            </p>
                            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- ピン留めボタン -->
                                <PinMessageButton
                                    :message-id="message.id"
                                    :channel-id="activeChannel.id"
                                    :is-pinned="messagePinStatus[message.id] || false"
                                    @pin-toggled="(isPinned, pinnedMessageId) => handlePinToggled(message.id, isPinned, pinnedMessageId)"
                                />
                                <button
                                    v-if="isOwnMessage(message)"
                                    @click="openEditModal(message)"
                                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 p-1 rounded"
                                    title="メッセージを編集"
                                >
                                    <PencilIcon class="h-4 w-4" />
                                </button>
                                <button
                                    v-if="isOwnMessage(message)"
                                    @click="openDeleteModal(message)"
                                    class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-1 rounded"
                                    title="メッセージを削除"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mt-1 prose prose-sm max-w-none dark:prose-invert">
                            <span v-if="message.content" v-html="md.render(message.content)" class="markdown-content"></span>
                        </div>
                        <!-- ファイル添付表示 -->
                        <div v-if="message.file_path" class="mt-2">
                            <img v-if="message.file_mime && message.file_mime.startsWith('image/')"
                                 :src="`/storage/${message.file_path}`"
                                 :alt="message.file_name"
                                 class="max-h-48 rounded border cursor-pointer hover:opacity-80 transition"
                                 @click="openPreview({
                                     type: 'image',
                                     src: `/storage/${message.file_path}`,
                                     name: message.file_name,
                                     mime: message.file_mime
                                 })"
                            />
                            <video v-else-if="message.file_mime && message.file_mime.startsWith('video/')"
                                   :src="`/storage/${message.file_path}`"
                                   controls
                                   class="max-h-48 rounded border cursor-pointer hover:opacity-80 transition"
                                   @click="openPreview({
                                       type: 'video',
                                       src: `/storage/${message.file_path}`,
                                       name: message.file_name,
                                       mime: message.file_mime
                                   })"
                            />
                            <audio v-else-if="message.file_mime && message.file_mime.startsWith('audio/')"
                                   :src="`/storage/${message.file_path}`"
                                   controls
                                   class="w-full cursor-pointer hover:opacity-80 transition"
                                   @click="openPreview({
                                       type: 'audio',
                                       src: `/storage/${message.file_path}`,
                                       name: message.file_name,
                                       mime: message.file_mime
                                   })"
                            />
                            <iframe v-else-if="message.file_mime === 'application/pdf'"
                                    :src="`/storage/${message.file_path}`"
                                    class="w-full max-h-96 border rounded cursor-pointer hover:opacity-80 transition"
                                    @click="openPreview({
                                        type: 'pdf',
                                        src: `/storage/${message.file_path}`,
                                        name: message.file_name,
                                        mime: message.file_mime
                                    })"
                            />
                            <iframe v-else-if="['application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'].includes(message.file_mime)"
                                    :src="`https://docs.google.com/gview?url=${location.origin}/storage/${message.file_path}&embedded=true`"
                                    class="w-full max-h-96 border rounded cursor-pointer hover:opacity-80 transition"
                                    @click="openPreview({
                                        type: 'doc',
                                        src: `https://docs.google.com/gview?url=${location.origin}/storage/${message.file_path}&embedded=true`,
                                        name: message.file_name,
                                        mime: message.file_mime
                                    })"
                            />
                            <div v-else>
                                <a :href="`/storage/${message.file_path}`" :download="message.file_name" class="text-blue-600 underline" target="_blank">
                                    {{ message.file_name }}
                                </a>
                                <span class="text-xs text-gray-400 ml-2">({{ (message.file_size / 1024).toFixed(1) }} KB)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="activeDirectMessage">
                <!-- DMメッセージ表示 -->
                <div
                    v-for="message in localMessages"
                    :key="message.id"
                    class="flex items-start mb-4 group hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg p-2 -m-2"
                >
                    <div class="w-10 h-10 rounded bg-purple-400 mr-4 shrink-0">
                        <img :src="message.user.avatar" alt="avatar" class="w-8 h-8 rounded-full" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-gray-900 dark:text-gray-100">
                                {{ message.user.name }}
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">{{ message.time }}</span>
                            </p>
                            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    v-if="isOwnMessage(message)"
                                    @click="openEditModal(message)"
                                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 p-1 rounded"
                                    title="メッセージを編集"
                                >
                                    <PencilIcon class="h-4 w-4" />
                                </button>
                                <button
                                    v-if="isOwnMessage(message)"
                                    @click="openDeleteModal(message)"
                                    class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-1 rounded"
                                    title="メッセージを削除"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mt-1 prose prose-sm max-w-none dark:prose-invert">
                            <span v-if="message.content" v-html="md.render(message.content)" class="markdown-content"></span>
                        </div>
                        <!-- ファイル添付表示 -->
                        <div v-if="message.file_path" class="mt-2">
                            <img v-if="message.file_mime && message.file_mime.startsWith('image/')"
                                 :src="`/storage/${message.file_path}`"
                                 :alt="message.file_name"
                                 class="max-h-48 rounded border cursor-pointer hover:opacity-80 transition"
                                 @click="openPreview({
                                     type: 'image',
                                     src: `/storage/${message.file_path}`,
                                     name: message.file_name,
                                     mime: message.file_mime
                                 })"
                            />
                            <video v-else-if="message.file_mime && message.file_mime.startsWith('video/')"
                                   :src="`/storage/${message.file_path}`"
                                   controls
                                   class="max-h-48 rounded border cursor-pointer hover:opacity-80 transition"
                                   @click="openPreview({
                                       type: 'video',
                                       src: `/storage/${message.file_path}`,
                                       name: message.file_name,
                                       mime: message.file_mime
                                   })"
                            />
                            <audio v-else-if="message.file_mime && message.file_mime.startsWith('audio/')"
                                   :src="`/storage/${message.file_path}`"
                                   controls
                                   class="w-full cursor-pointer hover:opacity-80 transition"
                                   @click="openPreview({
                                       type: 'audio',
                                       src: `/storage/${message.file_path}`,
                                       name: message.file_name,
                                       mime: message.file_mime
                                   })"
                            />
                            <iframe v-else-if="message.file_mime === 'application/pdf'"
                                    :src="`/storage/${message.file_path}`"
                                    class="w-full max-h-96 border rounded cursor-pointer hover:opacity-80 transition"
                                    @click="openPreview({
                                        type: 'pdf',
                                        src: `/storage/${message.file_path}`,
                                        name: message.file_name,
                                        mime: message.file_mime
                                    })"
                            />
                            <iframe v-else-if="['application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'].includes(message.file_mime)"
                                    :src="`https://docs.google.com/gview?url=${location.origin}/storage/${message.file_path}&embedded=true`"
                                    class="w-full max-h-96 border rounded cursor-pointer hover:opacity-80 transition"
                                    @click="openPreview({
                                        type: 'doc',
                                        src: `https://docs.google.com/gview?url=${location.origin}/storage/${message.file_path}&embedded=true`,
                                        name: message.file_name,
                                        mime: message.file_mime
                                    })"
                            />
                            <div v-else>
                                <a :href="`/storage/${message.file_path}`" :download="message.file_name" class="text-blue-600 underline" target="_blank">
                                    {{ message.file_name }}
                                </a>
                                <span class="text-xs text-gray-400 ml-2">({{ (message.file_size / 1024).toFixed(1) }} KB)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="text-center text-gray-400 mt-10">チャンネルまたはダイレクトメッセージを選択してください</div>
            </template>
        </div>

        <MessageInput v-if="activeChannel || activeDirectMessage" :active-channel="activeChannel" :active-direct-message="activeDirectMessage" @message-sent="handleMessageSent" />

        <!-- Message Edit Modal -->
        <MessageEditModal
            :show="showEditModal"
            :message="editingMessage"
            :active-direct-message="activeDirectMessage"
            @close="closeEditModal"
            @message-updated="handleMessageUpdated"
        />

        <!-- Message Delete Modal -->
        <MessageDeleteModal
            :show="showDeleteModal"
            :message="deletingMessage"
            :active-direct-message="activeDirectMessage"
            @close="closeDeleteModal"
            @message-deleted="handleMessageDeleted"
        />

        <DialogModal :show="showPreviewModal" max-width="4xl" @close="closePreview">
            <template #title>
                {{ previewFile?.name }}
            </template>
            <template #content>
                <div v-if="previewFile">
                    <img v-if="previewFile.type === 'image'" :src="previewFile.src" :alt="previewFile.name" class="max-h-[70vh] mx-auto rounded border" />
                    <video v-else-if="previewFile.type === 'video'" :src="previewFile.src" controls class="max-h-[70vh] mx-auto rounded border" />
                    <audio v-else-if="previewFile.type === 'audio'" :src="previewFile.src" controls class="w-full mx-auto" />
                    <iframe v-else-if="previewFile.type === 'pdf'" :src="previewFile.src" class="w-full min-h-[60vh] border rounded" />
                    <iframe v-else-if="previewFile.type === 'doc'" :src="previewFile.src" class="w-full min-h-[60vh] border rounded" />
                </div>
            </template>
            <template #footer>
                <button @click="closePreview" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">閉じる</button>
            </template>
        </DialogModal>
    </div>
</template> 