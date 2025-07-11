<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, ClockIcon, MagnifyingGlassIcon, UserCircleIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    activeChannel: Object,
    activeDirectMessage: Object,
});

const page = usePage();
const user = page.props.auth?.user;

const showUserMenu = ref(false);
const userMenuRef = ref(null);

// メッセージ検索関連
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const showSearchResults = ref(false);
const searchError = ref('');

// debounce関数
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

// メッセージ検索
const searchMessages = debounce(async (query) => {
    if (!query.trim()) {
        searchResults.value = [];
        showSearchResults.value = false;
        return;
    }
    
    searching.value = true;
    searchError.value = '';
    
    try {
        const response = await axios.get('/messages/search', { 
            params: { q: query.trim() } 
        });
        searchResults.value = response.data.messages;
        showSearchResults.value = true;
    } catch (error) {
        console.error('Search error:', error);
        searchError.value = '検索中にエラーが発生しました';
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
}, 300);

const onSearchInput = () => {
    searchMessages(searchQuery.value);
};

const clearSearch = () => {
    searchQuery.value = '';
    searchResults.value = [];
    showSearchResults.value = false;
    searchError.value = '';
};

const selectSearchResult = (message) => {
    // メッセージが属するチャンネルに移動
    router.visit(`/dashboard?channel=${message.channel.id}&message=${message.id}`);
    clearSearch();
};

// 検索キーワードをハイライトする関数
const highlightSearchTerm = (text, searchTerm) => {
    if (!searchTerm) return text;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 text-black px-1 rounded">$1</mark>');
};

const logout = () => {
    router.post(route('logout'));
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = () => {
    showUserMenu.value = false;
};

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        closeUserMenu();
    }
    
    // 検索結果の外側をクリックしたら閉じる
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer && !searchContainer.contains(event.target)) {
        showSearchResults.value = false;
    }
};

const showExportDialog = ref(false);
const exportFormat = ref('csv');
const exportStartDate = ref('');
const exportEndDate = ref('');
const exportLoading = ref(false);
const exportError = ref('');
const exportDownloadUrl = ref('');
const exportMessageCount = ref(0);

const openExportDialog = () => {
    showExportDialog.value = true;
    exportError.value = '';
    exportDownloadUrl.value = '';
    exportMessageCount.value = 0;
};
const closeExportDialog = () => {
    showExportDialog.value = false;
    exportError.value = '';
    exportDownloadUrl.value = '';
    exportMessageCount.value = 0;
};

const exportMessages = async () => {
    exportLoading.value = true;
    exportError.value = '';
    exportDownloadUrl.value = '';
    exportMessageCount.value = 0;
    
    try {
        let url = '';
        let payload = {
            format: exportFormat.value,
            start_date: exportStartDate.value || undefined,
            end_date: exportEndDate.value || undefined,
            include_files: false,
        };
        
        if (props.activeChannel) {
            url = '/api/messages/export/channel';
            payload.channel_id = props.activeChannel.id;
        } else if (props.activeDirectMessage) {
            url = '/api/messages/export/direct';
            payload.partner_id = props.activeDirectMessage.id;
        } else {
            exportError.value = 'チャンネルまたはDMを選択してください';
            exportLoading.value = false;
            return;
        }
        
        console.log('Exporting messages:', { url, payload });
        
        // CSRFトークンを取得
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        const res = await axios.post(url, payload, { headers });
        console.log('Export response:', res.data);
        
        if (res.data && res.data.success) {
            exportDownloadUrl.value = res.data.download_url;
            exportMessageCount.value = res.data.message_count;
        } else {
            exportError.value = res.data?.error || 'エクスポートに失敗しました';
        }
    } catch (e) {
        console.error('Export error:', e);
        console.error('Export error response:', e.response);
        
        if (e.response?.data?.error) {
            exportError.value = e.response.data.error;
        } else if (e.response?.status === 403) {
            exportError.value = 'アクセス権限がありません';
        } else if (e.response?.status === 404) {
            exportError.value = 'チャンネルまたはDMが見つかりません';
        } else if (e.response?.status === 422) {
            exportError.value = '入力データが正しくありません';
        } else if (e.response?.status >= 500) {
            exportError.value = 'サーバーエラーが発生しました';
        } else {
            exportError.value = 'エクスポートに失敗しました: ' + (e.message || '不明なエラー');
        }
    } finally {
        exportLoading.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="h-16 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between px-6">
        <div>
            <button class="flex items-center font-bold text-gray-900 dark:text-gray-100">
                <span v-if="activeChannel">{{ activeChannel.name }}</span>
                <span v-else-if="activeDirectMessage">{{ activeDirectMessage.name }}</span>
                <span v-else>チャンネルまたはダイレクトメッセージを選択してください</span>
                <ChevronDownIcon class="h-5 w-5 ml-1" />
            </button>
        </div>
        <div class="flex items-center space-x-4">
            <button @click="openExportDialog" class="flex items-center px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 focus:outline-none" title="メッセージをエクスポート">
                <ArrowDownTrayIcon class="h-5 w-5 mr-1" />
                エクスポート
            </button>
            <ThemeToggle />
            <ClockIcon class="h-6 w-6 text-gray-500 dark:text-gray-400" />
            <div class="relative search-container">
                <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    @input="onSearchInput"
                    @focus="showSearchResults = true"
                    type="text"
                    placeholder="メッセージを検索する"
                    class="bg-gray-100 dark:bg-gray-700 rounded-md w-80 pl-10 pr-8 py-1.5 focus:ring-blue-500 focus:border-blue-500 border-transparent text-gray-900 dark:text-gray-100"
                >
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
                
                <!-- 検索結果ドロップダウン -->
                <div 
                    v-if="showSearchResults && (searchResults.length > 0 || searching || searchError)"
                    class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto z-50"
                >
                    <!-- 検索中 -->
                    <div v-if="searching" class="p-4 text-center text-gray-500">
                        <div class="flex items-center justify-center">
                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            検索中...
                        </div>
                    </div>
                    
                    <!-- エラー -->
                    <div v-else-if="searchError" class="p-4 text-center text-red-500">
                        {{ searchError }}
                    </div>
                    
                    <!-- 検索結果 -->
                    <div v-else-if="searchResults.length > 0" class="py-2">
                        <div 
                            v-for="message in searchResults" 
                            :key="message.id"
                            @click="selectSearchResult(message)"
                            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                        >
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ message.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ message.user.name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">#{{ message.channel.name }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ message.date }} {{ message.time }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                        <span v-html="highlightSearchTerm(message.content, searchQuery)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 結果なし -->
                    <div v-else class="p-4 text-center text-gray-500">
                        "{{ searchQuery }}" に一致するメッセージが見つかりませんでした
                    </div>
                </div>
            </div>
            
            <!-- User Menu -->
            <div ref="userMenuRef" class="relative">
                <button
                    @click="toggleUserMenu"
                    class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none"
                >
                    <UserCircleIcon class="h-8 w-8" />
                    <span class="text-sm font-medium">{{ user?.name }}</span>
                    <ChevronDownIcon class="h-4 w-4" />
                </button>
                
                <!-- Dropdown Menu -->
                <div
                    v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700"
                >
                    <!-- プロフィールリンク（画像＋テキスト） -->
                    <a
                        :href="route('profile.show')"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700"
                    >
                        <img
                            :src="user?.profile_photo_url"
                            :alt="user?.name"
                            class="w-8 h-8 rounded-full object-cover mr-2 border border-gray-300 dark:border-gray-600"
                        >
                        <span>プロフィール</span>
                    </a>
                    <button
                        @click="logout"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        ログアウト
                    </button>
                </div>
            </div>
        </div>
        <!-- エクスポートダイアログ -->
        <div v-if="showExportDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">メッセージエクスポート</h2>
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">形式</label>
                    <select v-model="exportFormat" class="w-full border rounded px-2 py-1">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">開始日（任意）</label>
                    <input type="date" v-model="exportStartDate" class="w-full border rounded px-2 py-1" />
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">終了日（任意）</label>
                    <input type="date" v-model="exportEndDate" class="w-full border rounded px-2 py-1" />
                </div>
                <div class="flex items-center space-x-2 mt-4">
                    <button @click="exportMessages" :disabled="exportLoading" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-50">エクスポート</button>
                    <button @click="closeExportDialog" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">キャンセル</button>
                </div>
                <div v-if="exportLoading" class="mt-2 text-sm text-gray-500">エクスポート中...</div>
                <div v-if="exportError" class="mt-2 text-sm text-red-500">{{ exportError }}</div>
                <div v-if="exportDownloadUrl" class="mt-2 text-green-600">
                    <p>エクスポート成功（{{ exportMessageCount }}件）</p>
                    <a :href="exportDownloadUrl" class="underline text-blue-600" download>ダウンロード</a>
                </div>
            </div>
        </div>
    </div>
</template> 