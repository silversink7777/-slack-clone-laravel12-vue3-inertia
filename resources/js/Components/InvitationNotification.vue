<template>
  <div v-if="invitations.length > 0" class="space-y-6 max-w-md mx-auto">
    <div
      v-for="invitation in invitations"
      :key="invitation.id"
      :data-invitation-id="invitation.id"
      class="bg-white shadow rounded-xl p-5 flex flex-row items-center justify-between gap-4 border border-gray-100 transition-all duration-300"
    >
      <div class="flex items-center gap-4 flex-1 min-w-0">
        <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-xl font-bold text-indigo-600">
          {{ invitation.inviter.name.charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
          <div class="font-semibold text-lg text-gray-900 truncate">{{ invitation.channel.name }}</div>
          <div class="text-sm text-gray-500 truncate">招待者: <span class="font-medium text-indigo-700">{{ invitation.inviter.name }}</span></div>
          <div v-if="invitation.invitee" class="text-sm text-gray-500 truncate">
            招待先: <span class="font-medium text-indigo-700">{{ invitation.invitee.name || invitation.invitee.email }}</span>
          </div>
          <div class="flex flex-col gap-0.5 mt-1">
            <span class="text-xs text-gray-400">招待日時: {{ formatDate(invitation.created_at) }}</span>
            <span class="text-xs text-orange-600">有効期限: {{ formatDate(invitation.expires_at) }}</span>
          </div>
        </div>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2 min-w-fit">
        <button
          @click="respondToInvitation(invitation.id, 'accept')"
          class="px-4 py-2 rounded font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 transition disabled:opacity-60"
          :disabled="responding === invitation.id"
        >
          {{ responding === invitation.id ? '処理中...' : '承認' }}
        </button>
        <button
          @click="respondToInvitation(invitation.id, 'decline')"
          class="px-4 py-2 rounded font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition disabled:opacity-60"
          :disabled="responding === invitation.id"
        >
          {{ responding === invitation.id ? '処理中...' : '拒否' }}
        </button>
      </div>
    </div>
  </div>

  <!-- 招待通知がない場合のメッセージ -->
  <div v-else class="bg-white shadow rounded-lg max-w-lg mx-auto mt-8">
    <div class="px-4 py-5 sm:p-6 text-center">
      <div class="text-gray-400 mb-4">
        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 002 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">招待通知はありません</h3>
      <p class="text-gray-500">現在、あなたへの招待通知はありません。</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
  invitations: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['invitation-responded'])

const localInvitations = ref([])
const loading = ref(false)
const error = ref('')
const responding = ref(null)

// propsで招待データが渡された場合はそれを使用、そうでなければAPIから取得
const invitations = computed(() => {
  return props.invitations.length > 0 ? props.invitations : localInvitations.value
})

const loadInvitations = async () => {
  // propsで招待データが渡されている場合はAPI呼び出しをスキップ
  if (props.invitations.length > 0) {
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await fetch('/my-invitations')
    const data = await response.json()

    if (response.ok) {
      localInvitations.value = data
    } else {
      error.value = data.message || '招待の取得に失敗しました'
    }
  } catch (err) {
    error.value = '招待の取得に失敗しました'
  } finally {
    loading.value = false
  }
}

const respondToInvitation = async (invitationId, action) => {
  if (responding.value === invitationId) return

  responding.value = invitationId

  try {
    const response = await fetch(`/invitations/${invitationId}/respond`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ action })
    })

    const data = await response.json()

    if (response.ok) {
      // 招待一覧から削除
      if (props.invitations.length > 0) {
        // propsで渡されたデータの場合は、親コンポーネントで更新する必要がある
        emit('invitation-responded', { invitationId, action, data })
      } else {
        localInvitations.value = localInvitations.value.filter(inv => inv.id !== invitationId)
      }
      emit('invitation-responded', { invitationId, action, data })
      
      // 通知数を更新するためのイベントを発火
      window.dispatchEvent(new CustomEvent('invitation-count-updated'));
    } else {
      error.value = data.message || '招待の応答に失敗しました'
    }
  } catch (err) {
    error.value = '招待の応答に失敗しました'
  } finally {
    responding.value = null
  }
}

const markAllAsRead = () => {
  // 実装: すべての招待を既読にする処理
  console.log('Mark all as read')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadInvitations()
})
</script> 