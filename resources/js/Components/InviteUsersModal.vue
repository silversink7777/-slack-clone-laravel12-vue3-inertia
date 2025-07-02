<template>
  <Modal :show="show" @close="closeModal">
    <div class="p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">
        ユーザーを招待
      </h2>

      <!-- メールアドレスによる招待セクション -->
      <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-md font-medium text-gray-900 mb-3">
          メールアドレスで招待
        </h3>
        <div class="space-y-3">
          <div class="flex space-x-2">
            <input
              v-model="emailInput"
              type="email"
              placeholder="メールアドレスを入力"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @keyup.enter="addEmail"
            />
            <PrimaryButton
              @click="addEmail"
              :disabled="!emailInput.trim() || loading"
              class="px-4"
            >
              追加
            </PrimaryButton>
          </div>
          
          <!-- 追加されたメールアドレスの表示 -->
          <div v-if="emailList.length > 0" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              招待するメールアドレス ({{ emailList.length }}件)
            </label>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(email, index) in emailList"
                :key="index"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ email }}
                <button
                  @click="removeEmail(index)"
                  class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-500"
                >
                  ×
                </button>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- 既存ユーザーによる招待セクション -->
      <div class="mb-4">
        <h3 class="text-md font-medium text-gray-900 mb-3">
          既存ユーザーを選択
        </h3>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          招待するユーザーを選択
        </label>
        <div class="space-y-2 max-h-60 overflow-y-auto">
          <div
            v-for="user in availableUsers"
            :key="user.id"
            class="flex items-center space-x-3 p-2 border rounded hover:bg-gray-50"
          >
            <input
              type="checkbox"
              :id="'user-' + user.id"
              :value="user.id"
              v-model="selectedUsers"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <label :for="'user-' + user.id" class="flex-1 cursor-pointer">
              <div class="font-medium text-gray-900">{{ user.name }}</div>
              <div class="text-sm text-gray-500">{{ user.email }}</div>
            </label>
          </div>
        </div>
      </div>

      <div v-if="selectedUsers.length > 0" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          選択されたユーザー ({{ selectedUsers.length }}人)
        </label>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="userId in selectedUsers"
            :key="userId"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
          >
            {{ getUserName(userId) }}
            <button
              @click="removeUser(userId)"
              class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500"
            >
              ×
            </button>
          </span>
        </div>
      </div>

      <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ error }}
      </div>

      <div v-if="success" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ success }}
      </div>

      <div class="flex justify-end space-x-3">
        <SecondaryButton @click="closeModal">
          キャンセル
        </SecondaryButton>
        <PrimaryButton
          @click="sendInvitations"
          :disabled="(selectedUsers.length === 0 && emailList.length === 0) || loading"
          :loading="loading"
        >
          招待を送信
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from './Modal.vue'
import PrimaryButton from './PrimaryButton.vue'
import SecondaryButton from './SecondaryButton.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  channelId: {
    type: [String, Number],
    default: 0
  },
  currentUsers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'invitations-sent'])

const selectedUsers = ref([])
const availableUsers = ref([])
const emailInput = ref('')
const emailList = ref([])
const loading = ref(false)
const error = ref('')
const success = ref('')

// 利用可能なユーザー（既にメンバーのユーザーを除外）
const loadAvailableUsers = async () => {
  try {
    const response = await fetch('/users')
    const data = await response.json()

    if (response.ok) {
      // 既にメンバーのユーザーを除外
      availableUsers.value = data.data.filter(user => 
        !props.currentUsers.some(member => member.id === user.id)
      )
    } else {
      error.value = data.message || 'ユーザー一覧の取得に失敗しました'
    }
  } catch (err) {
    error.value = 'ユーザー一覧の取得に失敗しました'
    console.error('Load users error:', err)
  }
}

const getUserName = (userId) => {
  const user = availableUsers.value.find(u => u.id === userId)
  return user ? user.name : 'Unknown User'
}

const removeUser = (userId) => {
  selectedUsers.value = selectedUsers.value.filter(id => id !== userId)
}

// メールアドレスの追加
const addEmail = () => {
  const email = emailInput.value.trim()
  if (!email) return

  // メールアドレスの形式チェック
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email)) {
    error.value = '有効なメールアドレスを入力してください'
    return
  }

  // 重複チェック
  if (emailList.value.includes(email)) {
    error.value = 'このメールアドレスは既に追加されています'
    return
  }

  // 既存ユーザーのメールアドレスと重複チェック
  const existingUser = availableUsers.value.find(user => user.email === email)
  if (existingUser) {
    // 既存ユーザーの場合は、ユーザーIDリストに追加することを提案
    error.value = 'このメールアドレスのユーザーは既に選択可能なユーザーリストに含まれています。ユーザーリストから選択してください。'
    return
  }

  // 既に選択されたユーザーのメールアドレスと重複チェック
  const selectedUserEmails = selectedUsers.value.map(userId => {
    const user = availableUsers.value.find(u => u.id === userId)
    return user ? user.email : null
  }).filter(email => email)

  if (selectedUserEmails.includes(email)) {
    error.value = 'このメールアドレスのユーザーは既に選択されています'
    return
  }

  emailList.value.push(email)
  emailInput.value = ''
  error.value = ''
}

// メールアドレスの削除
const removeEmail = (index) => {
  emailList.value.splice(index, 1)
}

const sendInvitations = async () => {
  if (selectedUsers.value.length === 0 && emailList.value.length === 0) return

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await fetch(`/channels/${props.channelId}/invite`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        user_ids: selectedUsers.value,
        emails: emailList.value
      })
    })

    const data = await response.json()

    if (response.ok) {
      const totalInvitations = selectedUsers.value.length + emailList.value.length
      success.value = `${totalInvitations}件の招待を送信しました`
      selectedUsers.value = []
      emailList.value = []
      emit('invitations-sent', data)
      setTimeout(() => {
        closeModal()
      }, 2000)
    } else {
      error.value = data.message || '招待の送信に失敗しました'
    }
  } catch (err) {
    error.value = '招待の送信に失敗しました'
    console.error('Invitation error:', err)
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  selectedUsers.value = []
  emailList.value = []
  emailInput.value = ''
  error.value = ''
  success.value = ''
  emit('close')
}

// モーダルが開かれたときにエラーとサクセスメッセージをリセット
watch(() => props.show, (newVal) => {
  if (newVal) {
    error.value = ''
    success.value = ''
    loadAvailableUsers()
  }
})
</script> 