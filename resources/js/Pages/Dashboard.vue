<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    channels: Array,
    messages: Array,
    'direct-messages': Array,
});

// デバッグ情報を追加
console.log('=== Dashboard Component Debug ===');
console.log('Dashboard props:', props);
console.log('Channels prop type:', typeof props.channels);
console.log('Channels prop:', props.channels);
console.log('Channels prop length:', props.channels?.length);
console.log('Messages prop type:', typeof props.messages);
console.log('Messages prop:', props.messages);
console.log('Messages prop length:', props.messages?.length);

const channels = ref(
    (props.channels || []).map((channel, index) => ({ ...channel, active: index === 0 }))
);
const messages = ref(props.messages || []);
const activeChannel = ref(channels.value.find(c => c.active));

console.log('Initial channels ref:', channels.value);
console.log('Initial messages ref:', messages.value);
console.log('Active channel:', activeChannel.value);

const directMessages = ref(props['direct-messages'] || []);
const activeDirectMessage = ref(null);
const directMessagesContent = ref([]);

// DMリストのデバッグ情報
console.log('Direct messages prop:', props['direct-messages']);
console.log('Direct messages ref:', directMessages.value);

const handleSelectDirectMessage = async (userId) => {
    console.log('=== handleSelectDirectMessage called ===');
    console.log('Selected user ID:', userId);
    console.log('Current directMessages:', directMessages.value);
    
    // チャンネルのアクティブ状態をリセット
    channels.value = channels.value.map(c => ({ ...c, active: false }));
    activeChannel.value = null;
    
    activeDirectMessage.value = directMessages.value.find(dm => dm.id === userId) || null;
    console.log('Found activeDirectMessage:', activeDirectMessage.value);
    
    if (!activeDirectMessage.value) {
        console.error('Direct message partner not found for user ID:', userId);
        return;
    }
    
    try {
        console.log('Fetching conversation for partner ID:', userId);
        // CSRFトークンを取得
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        const response = await axios.get(`/direct-messages/conversation?partner_id=${userId}`, {
            headers: headers
        });
        console.log('Conversation response:', response.data);
        directMessagesContent.value = response.data;
    } catch (error) {
        console.error('Failed to fetch direct messages:', error);
        directMessagesContent.value = [];
    }
};

let onlineStatusInterval = null;
const manualOffline = ref(false);

// 初期化時にデフォルト値を設定
const initializeDirectMessages = () => {
    directMessages.value = props['direct-messages'] || [];
};

const setManualOffline = (value) => {
    manualOffline.value = value;
    if (manualOffline.value) {
        // オフラインに切り替えたらAPIで即反映
        axios.post('/user-online-status', { online: false });
        if (onlineStatusInterval) clearInterval(onlineStatusInterval);
    } else {
        // オンラインに戻したらAPIで即反映し、interval再開
        axios.post('/user-online-status', { online: true });
        startOnlineInterval();
    }
};

// オンライン状態APIからDMリストを取得
const fetchOnlineStatus = async () => {
    try {
        console.log('Fetching online status...');
        const response = await axios.get('/user-online-status');
        console.log('Online status response:', response);
        console.log('Response data:', response.data);
        
        if (response.data && Array.isArray(response.data)) {
            console.log('Processing online status data...');
            // 既存のDMリストを保持しつつ、オンライン状態を更新
            const onlineUsers = response.data.map(status => ({
                id: status.user_id,
                name: status.user?.name || 'Unknown',
                online: !!(status.online === true || status.online === 1 || status.online === '1'),
                active: false,
            }));
            
            // 既存のDMリストとオンラインユーザーをマージ
            const existingDMs = directMessages.value.filter(dm => 
                !onlineUsers.some(online => online.id === dm.id)
            );
            directMessages.value = [...existingDMs, ...onlineUsers];
            console.log('Final directMessages:', directMessages.value);
        } else {
            console.warn('Invalid response format from API');
            // DMリストは保持
        }
    } catch (error) {
        console.error('Failed to fetch user online status:', error);
        // DMリストは保持
    }
};

function startOnlineInterval() {
    if (onlineStatusInterval) clearInterval(onlineStatusInterval);
    onlineStatusInterval = setInterval(async () => {
        if (!manualOffline.value) {
            try {
                await axios.get('/user-online-status-update');
            } catch (e) {}
        }
        try {
            await fetchOnlineStatus();
        } catch (e) {}
    }, 10000);
}

onMounted(async () => {
    console.log('=== Dashboard Component Mounted ===');
    // 初期データを設定
    channels.value = (props.channels || []).map((channel, index) => ({ ...channel, active: index === 0 }));
    messages.value = props.messages || [];
    activeChannel.value = channels.value.find(c => c.active);
    
    // DMリストを初期化（propsから取得）
    directMessages.value = props['direct-messages'] || [];
    console.log('Initial directMessages from props:', directMessages.value);
    
    console.log('Mounted channels:', channels.value);
    console.log('Mounted messages:', messages.value);
    console.log('Mounted active channel:', activeChannel.value);
    
    // 最初のチャンネルのメッセージを読み込む
    if (activeChannel.value) {
        console.log('Loading messages for channel:', activeChannel.value.id);
        handleSelectChannel(activeChannel.value.id);
    } else {
        console.log('No active channel found');
    }

    // 最初に自分のオンライン状態を更新
    try {
        console.log('Updating own online status...');
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            console.log('CSRF token found:', token.content);
        } else {
            console.warn('CSRF token not found');
        }
        await axios.get('/user-online-status-update');
        console.log('Own online status updated successfully');
    } catch (error) {
        console.error('Failed to update own online status:', error);
        if (error.response && error.response.status === 419) {
            console.error('CSRF token error - page may need to be refreshed');
        }
    }

    // オンライン状態の更新（DMリストは保持）
    try {
        await fetchOnlineStatus();
    } catch (error) {
        console.error('Initial fetchOnlineStatus failed:', error);
        // DMリストは保持
    }
    startOnlineInterval();
});

onUnmounted(() => {
    if (onlineStatusInterval) clearInterval(onlineStatusInterval);
});

const handleSelectChannel = async (channelId) => {
    // DMをリセット
    activeDirectMessage.value = null;
    directMessagesContent.value = [];
    
    // チャンネルのアクティブ状態を更新
    channels.value = channels.value.map(c => ({
        ...c,
        active: c.id === channelId,
    }));
    activeChannel.value = channels.value.find(c => c.active);
    
    // 選択されたチャンネルのメッセージを取得
    try {
        const response = await axios.get(`/messages?channel_id=${channelId}`);
        messages.value = response.data;
    } catch (error) {
        console.error('Failed to fetch messages:', error);
        messages.value = [];
    }
};

const handleNewChannelAdded = async (newChannel) => {
    console.log('New channel added:', newChannel);
    
    // サーバーから最新のチャンネル一覧を再取得
    try {
        const response = await axios.get('/channels');
        console.log('Channels API response:', response.data);
        
        const updatedChannels = response.data.channels || [];
        console.log('Updated channels:', updatedChannels);
        
        // チャンネルリストを更新
        channels.value = updatedChannels.map((channel) => ({ 
            ...channel, 
            active: channel.id === newChannel.id 
        }));
        
        console.log('Updated channels.value:', channels.value);
        
        // アクティブなチャンネルを更新
        activeChannel.value = channels.value.find(c => c.active);
        console.log('Active channel:', activeChannel.value);
        
        // 新しいチャンネルのメッセージを取得
        if (activeChannel.value) {
            await handleSelectChannel(activeChannel.value.id);
        }
    } catch (error) {
        console.error('Failed to refresh channels after creation:', error);
        console.error('Error response:', error.response?.data);
        
        // エラーが発生した場合は、作成されたチャンネルを直接追加
        channels.value = channels.value.map(c => ({ ...c, active: false }));
        channels.value.push({ ...newChannel, active: true });
        activeChannel.value = channels.value.find(c => c.active);
        
        // 新しいチャンネルのメッセージを取得
        if (activeChannel.value) {
            await handleSelectChannel(activeChannel.value.id);
        }
    }
};

const handleMessageSent = (newMessage) => {
    if (activeChannel.value) {
        // チャンネルメッセージの場合
        messages.value.unshift(newMessage);
    } else if (activeDirectMessage.value) {
        // DMメッセージの場合
        directMessagesContent.value.unshift(newMessage);
    }
};

const handleMessageUpdated = (updatedMessage) => {
    if (activeChannel.value) {
        // チャンネルメッセージの場合
        const index = messages.value.findIndex(msg => msg.id === updatedMessage.id);
        if (index !== -1) {
            messages.value[index] = updatedMessage;
        }
    } else if (activeDirectMessage.value) {
        // DMメッセージの場合
        const index = directMessagesContent.value.findIndex(msg => msg.id === updatedMessage.id);
        if (index !== -1) {
            directMessagesContent.value[index] = updatedMessage;
        }
    }
};

const handleMessageDeleted = (deletedId) => {
    if (activeChannel.value) {
        // チャンネルメッセージの場合
        const idx = messages.value.findIndex(msg => msg.id === deletedId);
        if (idx !== -1) messages.value.splice(idx, 1);
    } else if (activeDirectMessage.value) {
        // DMメッセージの場合
        const idx = directMessagesContent.value.findIndex(msg => msg.id === deletedId);
        if (idx !== -1) directMessagesContent.value.splice(idx, 1);
    }
};

// チャンネル削除時の処理
const handleChannelDeleted = async (channelId) => {
    console.log('Channel deleted:', channelId);
    
    // 削除されたチャンネルをリストから削除
    channels.value = channels.value.filter(c => c.id !== channelId);
    
    // 削除されたチャンネルがアクティブだった場合、最初のチャンネルをアクティブにする
    if (activeChannel.value && activeChannel.value.id === channelId) {
        if (channels.value.length > 0) {
            await handleSelectChannel(channels.value[0].id);
        } else {
            activeChannel.value = null;
            messages.value = [];
        }
    }
};

// チャンネル退出時の処理
const handleChannelLeft = async (channelId) => {
    console.log('Channel left:', channelId);
    
    // 退出したチャンネルをリストから削除
    channels.value = channels.value.filter(c => c.id !== channelId);
    
    // 退出したチャンネルがアクティブだった場合、最初のチャンネルをアクティブにする
    if (activeChannel.value && activeChannel.value.id === channelId) {
        if (channels.value.length > 0) {
            await handleSelectChannel(channels.value[0].id);
        } else {
            activeChannel.value = null;
            messages.value = [];
        }
    }
};

// DM開始時の処理
const handleDirectMessageStarted = async (data) => {
    console.log('Direct message started:', data);
    
    // DMリストを更新
    const newPartner = {
        id: data.partner.id,
        name: data.partner.name,
        online: data.partner.online,
        active: false,
    };
    
    // 既存のDMリストに追加（重複を避ける）
    const existingIndex = directMessages.value.findIndex(dm => dm.id === newPartner.id);
    if (existingIndex === -1) {
        directMessages.value.push(newPartner);
    }
    
    // 新しいDMをアクティブにする
    await handleSelectDirectMessage(newPartner.id);
};
</script>

<template>
    <AppLayout
        title="Dashboard"
        :channels="channels"
        :direct-messages="directMessages"
        :messages="activeChannel ? messages : directMessagesContent"
        :active-channel="activeChannel"
        :active-direct-message="activeDirectMessage"
        :manual-offline="manualOffline"
        @toggle-manual-offline="setManualOffline"
        @select-channel="handleSelectChannel"
        @new-channel-added="handleNewChannelAdded"
        @message-sent="handleMessageSent"
        @message-updated="handleMessageUpdated"
        @message-deleted="handleMessageDeleted"
        @channel-deleted="handleChannelDeleted"
        @channel-left="handleChannelLeft"
        @select-direct-message="handleSelectDirectMessage"
        @direct-message-started="handleDirectMessageStarted"
    >
    </AppLayout>
</template>
