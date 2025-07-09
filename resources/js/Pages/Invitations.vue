<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import InvitationLayout from '@/Layouts/InvitationLayout.vue';
import InvitationNotification from '@/Components/InvitationNotification.vue';

const dmUnreadCount = ref(0);
const dmUnreadLoading = ref(false);
const dmUnreadError = ref('');

const fetchDmUnreadCount = async () => {
    dmUnreadLoading.value = true;
    dmUnreadError.value = '';
    try {
        const res = await fetch('/api/direct-messages/unread-count', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (res.ok && typeof data.count === 'number') {
            dmUnreadCount.value = data.count;
        } else {
            dmUnreadError.value = data.error || '未読DM件数の取得に失敗しました';
        }
    } catch (e) {
        dmUnreadError.value = '未読DM件数の取得に失敗しました';
    } finally {
        dmUnreadLoading.value = false;
    }
};

const props = defineProps({
    channels: {
        type: Array,
        default: () => []
    },
    'direct-messages': {
        type: Array,
        default: () => []
    },
    invitations: {
        type: Array,
        default: () => []
    },
    messages: {
        type: Array,
        default: () => []
    },
    manualOffline: {
        type: Boolean,
        default: false
    },
    isAuthenticated: {
        type: Boolean,
        default: false
    },
});

// URLパラメータから招待IDを取得
const urlParams = new URLSearchParams(window.location.search);
const invitationId = urlParams.get('invitation');
const registered = urlParams.get('registered');

// directMessagesを安全に処理
const safeDirectMessages = computed(() => {
    if (!props['direct-messages'] || !Array.isArray(props['direct-messages'])) {
        return [];
    }
    return props['direct-messages'];
});

// 未認証ユーザー向けの招待をフィルタリング
const unauthenticatedInvitations = computed(() => {
    return props.invitations.filter(invitation => !invitation.invitee_id);
});

// 登録完了メッセージの表示
const showRegistrationSuccess = ref(registered === 'true');

const handleInvitationResponded = (data) => {
    // 招待に応答された場合の処理
    if (data.action === 'accept') {
        // 成功メッセージを表示
        alert('チャンネルに参加しました！');
        // ページをリロードしてチャンネル一覧を更新
        window.location.reload();
    } else if (data.action === 'decline') {
        // 拒否メッセージを表示
        alert('招待を拒否しました。');
        // ページをリロード
        window.location.reload();
    }
};

// チャンネル選択時の処理
const handleSelectChannel = (channelId) => {
    // チャンネルページにリダイレクト
    router.visit(`/dashboard?channel=${channelId}`);
};

// オンライン状態切り替えの処理
const handleToggleManualOffline = (isOffline) => {
    // オンライン状態を更新
    router.put('/api/user/online-status', { manual_offline: isOffline });
};

// 新規チャンネル追加の処理
const handleNewChannelAdded = (channel) => {
    // 新規チャンネルが追加された場合の処理
    console.log('New channel added:', channel);
};

// デバッグ情報を表示
onMounted(() => {
    console.log('Invitations page props:', props);
    console.log('Invitations count:', props.invitations.length);
    console.log('Invitations data:', props.invitations);
    console.log('Invitation ID from URL:', invitationId);
    
    // 招待IDが指定されている場合、該当する招待をハイライト表示
    if (invitationId) {
        const targetInvitation = props.invitations.find(inv => inv.id == invitationId);
        if (targetInvitation) {
            console.log('Found target invitation:', targetInvitation);
            // 該当する招待までスクロール
            setTimeout(() => {
                const invitationElement = document.querySelector(`[data-invitation-id="${invitationId}"]`);
                if (invitationElement) {
                    invitationElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    invitationElement.classList.add('ring-2', 'ring-indigo-500', 'ring-opacity-50');
                }
            }, 500);
        }
    }
    fetchDmUnreadCount();
});
</script>

<template>
  <InvitationLayout
      title="招待通知"
      :channels="channels"
      :direct-messages="safeDirectMessages"
      :messages="messages"
      :manual-offline="manualOffline"
      @select-channel="handleSelectChannel"
      @toggle-manual-offline="handleToggleManualOffline"
      @new-channel-added="handleNewChannelAdded"
  >
    <div class="w-full max-w-lg mx-auto pt-8">
      <h1 class="text-2xl font-bold text-gray-900 text-center">招待通知</h1>
      <p class="text-gray-600 mt-2 text-center">あなたが招待されたチャンネルの通知がここに表示されます。</p>

      <!-- DM通知アラート -->
      <div v-if="dmUnreadCount > 0" class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg flex items-center justify-between">
        <div class="flex items-center">
          <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-purple-100 text-purple-600 mr-3">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8a9 9 0 100-18 9 9 0 000 18z" />
            </svg>
          </span>
          <span class="text-purple-800 font-semibold">新着ダイレクトメッセージが{{ dmUnreadCount }}件あります</span>
        </div>
        <button @click="router.visit('/direct-messages')" class="ml-4 px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors">DM画面へ</button>
      </div>
      <div v-if="dmUnreadError" class="mt-2 text-sm text-red-500">{{ dmUnreadError }}</div>

      <!-- 登録完了メッセージ -->
      <div v-if="showRegistrationSuccess" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
              <span class="text-green-600 text-sm font-medium">✅</span>
            </div>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800">
              アカウント作成完了！
            </h3>
            <p class="text-sm text-green-600 mt-1">
              アカウントが正常に作成され、チャンネルに自動参加しました。
            </p>
          </div>
        </div>
      </div>
      
      <!-- 未認証ユーザー向けの案内 -->
      <div v-if="!isAuthenticated && unauthenticatedInvitations.length > 0" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
              <span class="text-blue-600 text-sm font-medium">📝</span>
            </div>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">
              アカウントを作成してチャンネルに参加
            </h3>
            <p class="text-sm text-blue-600 mt-1">
              招待を受けるには、まずSlack Cloneのアカウントを作成してください。
            </p>
            <div class="mt-3">
              <a 
                :href="`/register?invitation=${unauthenticatedInvitations[0].id}&email=${encodeURIComponent(unauthenticatedInvitations[0].invitee_email)}`"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                アカウントを作成
              </a>
              <a 
                :href="`/login?email=${encodeURIComponent(unauthenticatedInvitations[0].invitee_email)}`"
                class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                既存アカウントでログイン
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-4">
        <InvitationNotification
          :invitations="invitations"
          @invitation-responded="handleInvitationResponded"
        />
      </div>
    </div>
  </InvitationLayout>
</template> 