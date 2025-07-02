<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, ClockIcon, MagnifyingGlassIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

defineProps({
    activeChannel: Object,
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

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="h-16 border-b border-gray-200 bg-white flex items-center justify-between px-6">
        <div>
            <button class="flex items-center font-bold">
                <span>{{ activeChannel?.name }}</span>
                <ChevronDownIcon class="h-5 w-5 ml-1" />
            </button>
        </div>
        <div class="flex items-center space-x-4">
            <ClockIcon class="h-6 w-6 text-gray-500" />
            <div class="relative search-container">
                <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    @input="onSearchInput"
                    @focus="showSearchResults = true"
                    type="text"
                    placeholder="メッセージを検索する"
                    class="bg-gray-100 rounded-md w-80 pl-10 pr-8 py-1.5 focus:ring-blue-500 focus:border-blue-500 border-transparent"
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
                    class="absolute top-full left-0 right-0 mt-1 bg-white rounded-md shadow-lg border border-gray-200 max-h-96 overflow-y-auto z-50"
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
                            class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
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
                                        <span class="text-sm font-medium text-gray-900">{{ message.user.name }}</span>
                                        <span class="text-xs text-gray-500">#{{ message.channel.name }}</span>
                                        <span class="text-xs text-gray-400">{{ message.date }} {{ message.time }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-700">
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
                    class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none"
                >
                    <UserCircleIcon class="h-8 w-8" />
                    <span class="text-sm font-medium">{{ user?.name }}</span>
                    <ChevronDownIcon class="h-4 w-4" />
                </button>
                
                <!-- Dropdown Menu -->
                <div
                    v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                >
                    <button
                        @click="logout"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        ログアウト
                    </button>
                </div>
            </div>
        </div>
    </div>
</template> 