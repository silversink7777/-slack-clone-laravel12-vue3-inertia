<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { ChevronDownIcon, HashtagIcon, PlusIcon, UserPlusIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/solid';
import AddChannelModal from '@/Components/AddChannelModal.vue';
import InviteUsersModal from '@/Components/InviteUsersModal.vue';
import axios from 'axios';

const props = defineProps({
    channels: Array,
    'direct-messages': Array,
    manualOffline: Boolean,
});

// propsの安全な処理
const safeChannels = computed(() => props.channels || []);
const safeDirectMessages = computed(() => props['direct-messages'] || []);

const emit = defineEmits(['selectChannel', 'newChannelAdded', 'toggleManualOffline', 'channelDeleted', 'channelLeft']);

const showAddChannelModal = ref(false);
const showInviteModal = ref(false);
const selectedChannelForInvite = ref(0); // nullではなく0で初期化
const channelMembers = ref({}); // チャンネルIDをキーとしたオブジェクトに変更

// チャンネルメニュー関連
const showChannelMenu = ref(false);
const selectedChannelForMenu = ref(null);
const channelMenuPosition = ref({ x: 0, y: 0 });

const searchQuery = ref('');
const searching = ref(false);
const searchError = ref('');
const filteredChannels = ref([...safeChannels.value]);
const isSearchMode = ref(false);

const selectChannel = (id) => {
    emit('selectChannel', id);
};

const handleCreateChannel = async (name) => {
    try {
        // CSRFトークンを取得
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        const response = await axios.post('/channels', { name }, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
        });
        emit('newChannelAdded', response.data);
        showAddChannelModal.value = false;
    } catch (error) {
        let msg = 'チャンネル作成に失敗しました';
        if (error.response && error.response.status === 419) {
            msg = 'セッションの有効期限が切れています。ページを再読み込みしてください。';
        } else if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message;
        }
        alert(msg);
        console.error('Channel creation failed:', error);
    }
};

// チャンネル削除
const handleDeleteChannel = async (channelId) => {
    if (!confirm('このチャンネルを削除しますか？この操作は取り消せません。')) {
        return;
    }

    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        await axios.delete(`/channels/${channelId}`, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
        });
        
        emit('channelDeleted', channelId);
        showChannelMenu.value = false;
        alert('チャンネルを削除しました');
    } catch (error) {
        let msg = 'チャンネル削除に失敗しました';
        if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message;
        }
        alert(msg);
        console.error('Channel deletion failed:', error);
    }
};

// チャンネル退出
const handleLeaveChannel = async (channelId) => {
    if (!confirm('このチャンネルから退出しますか？')) {
        return;
    }

    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        await axios.post(`/channels/${channelId}/leave`, {}, {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
        });
        
        emit('channelLeft', channelId);
        showChannelMenu.value = false;
        alert('チャンネルから退出しました');
    } catch (error) {
        let msg = 'チャンネル退出に失敗しました';
        if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message;
        }
        alert(msg);
        console.error('Channel leave failed:', error);
    }
};

// チャンネルメニューを表示
const showChannelMenuForChannel = (channel, event) => {
    event.stopPropagation();
    selectedChannelForMenu.value = channel;
    channelMenuPosition.value = { x: event.clientX, y: event.clientY };
    showChannelMenu.value = true;
};

// メニューを閉じる
const closeChannelMenu = () => {
    showChannelMenu.value = false;
    selectedChannelForMenu.value = null;
};

// チャンネルメンバー情報を取得する関数
const getChannelMembers = async (channelId) => {
    try {
        const response = await axios.get(`/channels/${channelId}/members`);
        channelMembers.value[channelId] = response.data;
        return response.data;
    } catch (error) {
        console.error('Error fetching channel members:', error);
        channelMembers.value[channelId] = [];
        return [];
    }
};

// 全チャンネルのメンバー情報を一括取得
const fetchAllChannelMembers = async () => {
    if (safeChannels.value.length === 0) return;
    
    const promises = safeChannels.value.map(async (channel) => {
        if (!channelMembers.value[channel.id]) {
            await getChannelMembers(channel.id);
        }
    });
    
    await Promise.all(promises);
};

// チャンネル一覧が変更された時にメンバー情報を取得
watch(safeChannels, async (newChannels) => {
    if (newChannels.length > 0) {
        await fetchAllChannelMembers();
    }
}, { immediate: true });

const showInviteModalForChannel = async (channelId) => {
    selectedChannelForInvite.value = channelId;
    
    // メンバー情報が未取得の場合は取得
    if (!channelMembers.value[channelId]) {
        await getChannelMembers(channelId);
    }
    
    showInviteModal.value = true;
};

const handleInviteButtonClick = (channelId) => {
    showInviteModalForChannel(channelId);
};

const handleInvitationSent = (data) => {
    // 必要に応じてチャンネル情報を更新
};

const isChannelAdmin = (channelId) => {
    // チャンネルメンバー情報から管理者権限を判定
    const currentUserId = document.querySelector('meta[name="user-id"]')?.content;
    
    if (!currentUserId) {
        return false;
    }
    
    const members = channelMembers.value[channelId] || [];
    const member = members.find(m => m.id === parseInt(currentUserId));
    
    return member && member.role === 'admin';
};

// クリックイベントでメニューを閉じる
onMounted(() => {
    document.addEventListener('click', closeChannelMenu);
});

// コンポーネントがアンマウントされた時にイベントリスナーを削除
import { onUnmounted } from 'vue';
onUnmounted(() => {
    document.removeEventListener('click', closeChannelMenu);
});

// debounceの自作関数を追加
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const later = () => {
            timeout = null;
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const fetchSearchedChannels = debounce(async (q) => {
    if (!q.trim()) {
        filteredChannels.value = [...safeChannels.value];
        searching.value = false;
        searchError.value = '';
        isSearchMode.value = false;
        return;
    }
    
    searching.value = true;
    searchError.value = '';
    isSearchMode.value = true;
    
    try {
        const response = await axios.get('/channels/search', { params: { q: q.trim() } });
        filteredChannels.value = response.data.channels.map(c => ({ 
            ...c, 
            active: safeChannels.value.find(s => s.id === c.id)?.active 
        }));
    } catch (e) {
        console.error('Search error:', e);
        searchError.value = '検索中にエラーが発生しました';
        filteredChannels.value = [];
    } finally {
        searching.value = false;
    }
}, 300);

const onSearchInput = () => {
    fetchSearchedChannels(searchQuery.value);
};

const clearSearch = () => {
    searchQuery.value = '';
    filteredChannels.value = [...safeChannels.value];
    searchError.value = '';
    isSearchMode.value = false;
};

// 検索キーワードをハイライトする関数
const highlightSearchTerm = (text, searchTerm) => {
    if (!searchTerm) return text;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 text-black px-1 rounded">$1</mark>');
};

watch(safeChannels, (newChannels) => {
    if (!searchQuery.value) {
        filteredChannels.value = [...newChannels];
    }
}, { immediate: true });
</script>

<template>
    <div class="w-64 bg-slack-purple-dark text-slack-purple-light flex flex-col">
        <div class="px-4 h-16 flex items-center justify-between font-bold text-white text-lg border-b border-purple-800">
            <span>test</span>
            <div class="flex items-center space-x-2">
                <span :class="manualOffline ? 'bg-gray-400' : 'bg-green-500'" class="h-3 w-3 rounded-full inline-block"></span>
                <button @click="emit('toggleManualOffline', !manualOffline)" class="text-xs bg-gray-700 px-2 py-1 rounded hover:bg-gray-600">
                    {{ manualOffline ? 'オフライン中' : 'オンライン中' }}
                </button>
            </div>
            <ChevronDownIcon class="h-5 w-5" />
        </div>
        <!-- 検索ボックス -->
        <div class="p-2">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    @input="onSearchInput"
                    type="text"
                    placeholder="チャンネル検索..."
                    class="w-full px-2 py-1 pr-8 rounded border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-400"
                />
                <button 
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    title="検索をクリア"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
           <div class="mb-4">
                <div class="flex items-center justify-between px-2">
                    <h2 class="text-sm font-bold flex items-center">
                        <ChevronDownIcon class="h-4 w-4 mr-1" />
                        {{ isSearchMode ? '検索結果' : 'チャンネル' }}
                        <span v-if="isSearchMode" class="ml-2 text-xs text-gray-400">({{ filteredChannels.length }})</span>
                    </h2>
                    <button 
                        v-if="!isSearchMode"
                        @click="showAddChannelModal = true" 
                        class="text-slack-purple-light hover:text-white"
                    >
                        <PlusIcon class="h-4 w-4" />
                    </button>
                </div>
                <ul>
                    <li
                        v-for="channel in filteredChannels"
                        :key="channel.id"
                        class="group flex items-center justify-between rounded px-2 py-1"
                        :class="{
                            'text-slack-active-item-text bg-slack-active-item': channel.active,
                            'hover:bg-purple-600': !channel.active
                        }"
                    >
                        <div @click="selectChannel(channel.id)" class="flex items-center flex-1 cursor-pointer">
                            <HashtagIcon class="h-5 w-5 mr-2" />
                            <span v-if="isSearchMode && searchQuery" v-html="highlightSearchTerm(channel.name, searchQuery)"></span>
                            <span v-else>{{ channel.name }}</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <!-- チャンネル管理者の場合のみ招待ボタンを表示 -->
                            <button 
                                v-if="isChannelAdmin(channel.id)"
                                @click.stop="handleInviteButtonClick(channel.id)"
                                class="text-slack-purple-light hover:text-white transition-opacity"
                                :title="`${channel.name}にユーザーを招待`"
                            >
                                <UserPlusIcon class="h-4 w-4" />
                            </button>
                            <!-- チャンネルメニューボタン -->
                            <button 
                                @click.stop="showChannelMenuForChannel(channel, $event)"
                                class="text-slack-purple-light hover:text-white transition-opacity opacity-0 group-hover:opacity-100"
                                :title="`${channel.name}のメニュー`"
                            >
                                <EllipsisVerticalIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                </ul>
                <div v-if="searching" class="text-xs text-gray-400 px-2 py-1 flex items-center">
                    <svg class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    検索中...
                </div>
                <div v-if="searchError" class="text-xs text-red-400 px-2 py-1">{{ searchError }}</div>
                <div v-if="!searching && !searchError && isSearchMode && filteredChannels.length === 0" class="text-xs text-gray-400 px-2 py-1">
                    "{{ searchQuery }}" に一致するチャンネルが見つかりませんでした
                </div>
           </div>
           <div class="mb-4">
                <h2 class="text-sm font-bold px-2 flex items-center">
                    <ChevronDownIcon class="h-4 w-4 mr-1" />
                    ダイレクトメッセージ ({{ safeDirectMessages.length }})
                </h2>
                <ul>
                    <li
                        v-for="dm in safeDirectMessages"
                        :key="dm.id"
                        class="flex items-center rounded px-2 py-1 hover:bg-purple-600 cursor-pointer"
                    >
                        <span class="h-3 w-3 rounded-full mr-2" :class="{'bg-green-500': !!dm.online, 'bg-gray-400': !dm.online}"></span>
                        {{ dm.name }}
                    </li>
                </ul>
           </div>
        </div>

        <AddChannelModal
            :show="showAddChannelModal"
            @create-channel="handleCreateChannel"
            @close="showAddChannelModal = false"
        />

        <!-- InviteUsersModalを表示 -->
        <InviteUsersModal
            :show="showInviteModal"
            :channel-id="selectedChannelForInvite"
            :current-users="channelMembers[selectedChannelForInvite] || []"
            @close="showInviteModal = false"
            @invitations-sent="handleInvitationSent"
        />

        <!-- チャンネルメニュードロップダウン -->
        <div 
            v-if="showChannelMenu && selectedChannelForMenu"
            class="fixed z-50 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-48"
            :style="{
                left: channelMenuPosition.x + 'px',
                top: channelMenuPosition.y + 'px'
            }"
        >
            <div class="px-4 py-2 border-b border-gray-100">
                <div class="font-medium text-gray-900">#{{ selectedChannelForMenu.name }}</div>
                <div class="text-sm text-gray-500">チャンネルメニュー</div>
            </div>
            
            <!-- 管理者の場合のみ削除オプションを表示 -->
            <button 
                v-if="isChannelAdmin(selectedChannelForMenu.id)"
                @click="handleDeleteChannel(selectedChannelForMenu.id)"
                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center"
            >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                チャンネルを削除
            </button>
            
            <!-- 退出オプション（管理者でも一般メンバーでも表示） -->
            <button 
                @click="handleLeaveChannel(selectedChannelForMenu.id)"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
            >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                チャンネルから退出
            </button>
        </div>
    </div>
</template> 