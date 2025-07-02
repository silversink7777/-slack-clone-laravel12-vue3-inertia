<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
        招待一覧
      </h3>

      <div v-if="loading" class="text-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">読み込み中...</p>
      </div>

      <div v-else-if="invitations.length === 0" class="text-center py-8">
        <div class="text-gray-400 mb-2">
          <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </div>
        <p class="text-gray-500">招待はありません</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="invitation in invitations"
          :key="invitation.id"
          class="border rounded-lg p-4 hover:bg-gray-50"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <span class="text-indigo-600 font-medium">
                      {{ invitation.invitee.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">
                    {{ invitation.invitee.name }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ invitation.invitee.email }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">
                    招待者: {{ invitation.inviter.name }} | 
                    {{ formatDate(invitation.created_at) }}
                  </p>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <span
                :class="getStatusClass(invitation.status)"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ getStatusText(invitation.status) }}
              </span>

              <div v-if="invitation.status === 'pending'" class="flex space-x-1">
                <button
                  @click="cancelInvitation(invitation.id)"
                  class="text-red-600 hover:text-red-800 text-sm"
                  :disabled="cancelling === invitation.id"
                >
                  {{ cancelling === invitation.id ? '取り消し中...' : '取り消し' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="invitation.status === 'pending'" class="mt-3 pt-3 border-t border-gray-200">
            <p class="text-xs text-gray-500">
              有効期限: {{ formatDate(invitation.expires_at) }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="error" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  channelId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['invitation-cancelled'])

const invitations = ref([])
const loading = ref(false)
const error = ref('')
const cancelling = ref(null)

const loadInvitations = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch(`/channels/${props.channelId}/invitations`)
    const data = await response.json()

    if (response.ok) {
      invitations.value = data
    } else {
      error.value = data.message || '招待一覧の取得に失敗しました'
    }
  } catch (err) {
    error.value = '招待一覧の取得に失敗しました'
    console.error('Load invitations error:', err)
  } finally {
    loading.value = false
  }
}

const cancelInvitation = async (invitationId) => {
  if (cancelling.value === invitationId) return

  cancelling.value = invitationId

  try {
    const response = await fetch(`/invitations/${invitationId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      // 招待一覧から削除
      invitations.value = invitations.value.filter(inv => inv.id !== invitationId)
      emit('invitation-cancelled', invitationId)
    } else {
      const data = await response.json()
      error.value = data.message || '招待の取り消しに失敗しました'
    }
  } catch (err) {
    error.value = '招待の取り消しに失敗しました'
    console.error('Cancel invitation error:', err)
  } finally {
    cancelling.value = null
  }
}

const getStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'accepted':
      return 'bg-green-100 text-green-800'
    case 'declined':
      return 'bg-red-100 text-red-800'
    case 'expired':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'pending':
      return '保留中'
    case 'accepted':
      return '承認済み'
    case 'declined':
      return '拒否'
    case 'expired':
      return '期限切れ'
    default:
      return '不明'
  }
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