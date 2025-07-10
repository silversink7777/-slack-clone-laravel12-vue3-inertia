<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import ChannelListSidebar from '@/Components/ChannelListSidebar.vue';
import MainContent from '@/Components/MainContent.vue';

const props = defineProps({
    title: String,
    channels: Array,
    'direct-messages': Array,
    directMessages: Array, // キャメルケース版も追加
    messages: Array,
    activeChannel: Object,
    activeDirectMessage: Object, // 追加
    manualOffline: Boolean,
});

const emit = defineEmits(['selectChannel', 'newChannelAdded', 'messageSent', 'messageUpdated', 'messageDeleted', 'toggleManualOffline', 'channelDeleted', 'channelLeft', 'selectDirectMessage', 'directMessageStarted']);

// directMessagesとdirect-messagesの両方に対応
const directMessagesList = computed(() => {
    return props.directMessages || props['direct-messages'] || [];
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="flex h-screen bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
            <WorkspaceSidebar />
            <ChannelListSidebar
                :channels="channels"
                :direct-messages="directMessagesList"
                :manual-offline="manualOffline"
                @toggle-manual-offline="emit('toggleManualOffline', $event)"
                @select-channel="id => emit('selectChannel', id)"
                @new-channel-added="channel => emit('newChannelAdded', channel)"
                @channel-deleted="id => emit('channelDeleted', id)"
                @channel-left="id => emit('channelLeft', id)"
                @select-direct-message="id => emit('selectDirectMessage', id)"
                @direct-message-started="data => emit('directMessageStarted', data)"
            />
            <div class="flex-1 flex flex-col">
                <!-- 招待通知エリア -->
                <slot name="notifications"></slot>
                
                <!-- メインコンテンツ -->
                <slot />
                <MainContent 
                    v-if="activeChannel || activeDirectMessage"
                    :messages="messages" 
                    :active-channel="activeChannel"
                    :active-direct-message="activeDirectMessage"
                    @message-sent="message => emit('messageSent', message)"
                    @message-updated="message => emit('messageUpdated', message)"
                    @message-deleted="id => emit('messageDeleted', id)"
                />
            </div>
        </div>
    </div>
</template>
