<template>
    <DirectMessagesLayout title="ダイレクトメッセージ">
        <div class="flex h-screen bg-gray-100 dark:bg-gray-900">
            <!-- 左サイドバー：ユーザー検索・DMパートナー一覧 -->
            <div class="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-full">
                <!-- ヘッダー -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            ダイレクトメッセージ
                        </h2>
                        <a
                            href="/"
                            class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 transition-colors"
                            title="トップ画面に戻る"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- ユーザー検索 -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="ユーザー名またはメールアドレスで検索..."
                            class="w-full px-3 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- 検索結果 -->
                <div v-if="searching" class="p-4 text-center">
                    <svg class="animate-spin h-6 w-6 mx-auto text-purple-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">検索中...</p>
                </div>

                <!-- 検索結果リスト -->
                <div v-else-if="searchResults.length > 0" class="flex-1 overflow-y-auto">
                    <div class="p-2">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 px-2">検索結果</h3>
                        <div
                            v-for="user in searchResults"
                            :key="user.id"
                            class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer mb-1"
                            @click="startDirectMessage(user)"
                        >
                            <div class="flex items-center">
                                <span class="h-3 w-3 rounded-full mr-3" :class="{'bg-green-500': user.online, 'bg-gray-400': !user.online}"></span>
                                <div class="flex flex-col">
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ user.name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</span>
                                </div>
                            </div>
                            <button
                                @click.stop="startDirectMessage(user)"
                                class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors"
                            >
                                DM開始
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DMパートナー一覧 -->
                <div v-else class="flex-1 overflow-y-auto">
                    <div class="p-2">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 px-2">最近の会話</h3>
                        <div
                            v-for="partner in directMessagePartners"
                            :key="partner.id"
                            class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer mb-1"
                            :class="{'bg-purple-50 dark:bg-purple-900': activePartner?.id === partner.id}"
                            @click="selectPartner(partner)"
                        >
                            <span class="h-3 w-3 rounded-full mr-3" :class="{'bg-green-500': partner.online, 'bg-gray-400': !partner.online}"></span>
                            <div class="flex flex-col">
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ partner.name }}</span>
                                <span v-if="partner.email" class="text-xs text-gray-500 dark:text-gray-400">{{ partner.email }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 検索結果なし -->
                <div v-if="searchQuery && !searching && searchResults.length === 0" class="p-4 text-center">
                    <p class="text-gray-500 dark:text-gray-400">ユーザーが見つかりませんでした</p>
                </div>
            </div>

            <!-- 右側：メッセージ表示エリア -->
            <div class="flex-1 flex flex-col h-full bg-gray-100 dark:bg-gray-900 min-h-0">
                <!-- メッセージ表示エリア -->
                <div v-if="activePartner" class="flex-1 flex flex-col bg-gray-100">
                    <!-- パートナーヘッダー -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="h-3 w-3 rounded-full mr-3" :class="{'bg-green-500': activePartner.online, 'bg-gray-400': !activePartner.online}"></span>
                                <div class="flex flex-col">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ activePartner.name }}
                                    </h3>
                                    <span v-if="activePartner.email" class="text-sm text-gray-500 dark:text-gray-400">{{ activePartner.email }}</span>
                                </div>
                            </div>
                            <a
                                href="/"
                                class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 transition-colors"
                                title="トップ画面に戻る"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- メッセージリスト -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 h-full min-h-0">
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="flex"
                            :class="message.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                                :class="message.sender_id === currentUserId 
                                    ? 'bg-purple-600 text-white' 
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                            >
                                <p class="text-sm">{{ message.content }}</p>
                                <p class="text-xs mt-1 opacity-75">{{ message.time }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- メッセージ入力 -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex space-x-2">
                            <input
                                v-model="newMessage"
                                @keyup.enter="sendMessage"
                                type="text"
                                placeholder="メッセージを入力..."
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                            <button
                                @click="sendMessage"
                                :disabled="!newMessage.trim()"
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                送信
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 初期表示 -->
                <div v-else class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">ダイレクトメッセージ</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            ユーザーを検索してDMを開始するか、最近の会話を選択してください
                        </p>
                        <div class="mt-4">
                            <a
                                href="/"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700 transition-colors"
                            >
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                トップ画面に戻る
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DirectMessagesLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import DirectMessagesLayout from '@/Layouts/DirectMessagesLayout.vue';
import axios from 'axios';

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const directMessagePartners = ref([]);
const activePartner = ref(null);
const messages = ref([]);
const newMessage = ref('');
const currentUserId = ref(null);

// 検索のデバウンス
let searchTimeout = null;

const onSearchInput = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        if (searchQuery.value.trim()) {
            searchUsers();
        } else {
            searchResults.value = [];
        }
    }, 300);
};

const searchUsers = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    searching.value = true;
    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {};
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        const response = await axios.get('/users/search', {
            params: { q: searchQuery.value.trim() },
            headers: headers
        });
        searchResults.value = response.data.users || [];
    } catch (error) {
        console.error('Failed to search users:', error);
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
};

const startDirectMessage = async (user) => {
    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        // DMを開始（初回メッセージなしで）
        const response = await axios.post('/direct-messages/start', {
            partner_id: user.id,
            message: 'こんにちは！'
        }, {
            headers: headers
        });

        // パートナーを追加
        const newPartner = {
            id: user.id,
            name: user.name,
            email: user.email,
            online: user.online,
        };

        // 既存のパートナーリストに追加（重複を避ける）
        const existingIndex = directMessagePartners.value.findIndex(p => p.id === newPartner.id);
        if (existingIndex === -1) {
            directMessagePartners.value.unshift(newPartner);
        }

        // 新しいパートナーを選択
        await selectPartner(newPartner);

        // 検索結果をクリア
        searchQuery.value = '';
        searchResults.value = [];

    } catch (error) {
        console.error('Failed to start direct message:', error);
        alert('DMの開始に失敗しました。');
    }
};

const selectPartner = async (partner) => {
    activePartner.value = partner;
    messages.value = [];

    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        const response = await axios.get(`/direct-messages/conversation?partner_id=${partner.id}`, {
            headers: headers
        });
        messages.value = response.data;
    } catch (error) {
        console.error('Failed to fetch conversation:', error);
        messages.value = [];
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !activePartner.value) {
        return;
    }

    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        const response = await axios.post('/direct-messages', {
            content: newMessage.value.trim(),
            receiver_id: activePartner.value.id
        }, {
            headers: headers
        });

        // 新しいメッセージをリストに追加
        messages.value.push(response.data);

        // 入力フィールドをクリア
        newMessage.value = '';

    } catch (error) {
        console.error('Failed to send message:', error);
        if (error.response) {
            console.error('Error response:', error.response.data);
            if (error.response.status === 405) {
                alert('メッセージの送信方法が正しくありません。ページを再読み込みしてください。');
            } else if (error.response.data && error.response.data.error) {
                alert(`メッセージの送信に失敗しました: ${error.response.data.error}`);
            } else {
                alert('メッセージの送信に失敗しました。');
            }
        } else {
            alert('メッセージの送信に失敗しました。');
        }
    }
};

const loadDirectMessagePartners = async () => {
    try {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        const headers = {};
        if (token) {
            headers['X-CSRF-TOKEN'] = token.content;
        }

        const response = await axios.get('/api/direct-messages/partners', {
            headers: headers
        });
        directMessagePartners.value = response.data.map(partner => ({
            id: partner.id,
            name: partner.name,
            email: partner.email,
            online: partner.online || false,
        }));
    } catch (error) {
        console.error('Failed to load direct message partners:', error);
        directMessagePartners.value = [];
    }
};

onMounted(async () => {
    // 現在のユーザーIDを取得
    try {
        const response = await axios.get('/api/user');
        currentUserId.value = response.data.id;
    } catch (error) {
        console.error('Failed to get current user:', error);
    }

    // DMパートナー一覧を読み込み
    await loadDirectMessagePartners();
});
</script> 