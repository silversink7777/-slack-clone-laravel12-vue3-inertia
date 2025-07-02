<template>
  <AdminLayout :admin="admin">
    <div class="space-y-6">
      <!-- ヘッダー -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">メッセージ管理</h1>
          <p class="text-gray-600">全メッセージの管理・検索・削除ができます</p>
        </div>
      </div>
      <!-- 検索 -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <form @submit.prevent="searchMessages" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">検索</label>
              <input v-model="filters.search" type="text" placeholder="メッセージ内容" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ユーザー</label>
              <select v-model="filters.user_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">すべて</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">チャンネル</label>
              <select v-model="filters.channel_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">すべて</option>
                <option v-for="channel in channels" :key="channel.id" :value="channel.id">{{ channel.name }}</option>
              </select>
            </div>
            <div class="flex items-end">
              <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">検索</button>
            </div>
          </div>
        </form>
      </div>
      <!-- 一覧 -->
      <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">メッセージ一覧 ({{ messages.total }}件)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">内容</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">チャンネル</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="message in messages.data" :key="message.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">{{ message.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap max-w-xs truncate">{{ message.content }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ message.user?.name || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ message.channel?.name || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(message.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <Link :href="route('admin.messages.show', message.id)" class="text-blue-600 hover:text-blue-900">詳細</Link>
                    <button @click="confirmDelete(message)" class="text-red-600 hover:text-red-900">削除</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- ページネーション -->
        <div v-if="messages.links" class="px-6 py-3 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">{{ messages.from }} - {{ messages.to }} / {{ messages.total }}件</div>
            <div class="flex space-x-2">
              <Link v-for="link in messages.links" :key="link.label" v-if="link && link.url" :href="link.url" :class="['px-3 py-2 text-sm rounded-lg', link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50']" v-html="link.label" />
              <span v-else-if="link" :class="['px-3 py-2 text-sm rounded-lg cursor-not-allowed', 'bg-gray-100 text-gray-400 border border-gray-200']" v-html="link.label" />
            </div>
          </div>
        </div>
      </div>
      <!-- 削除モーダル -->
      <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeDeleteModal">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
          <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
              <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">メッセージを削除</h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500">「{{ messageToDelete?.content }}」を削除しますか？この操作は取り消せません。</p>
            </div>
            <div class="flex justify-center space-x-4 mt-4">
              <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">キャンセル</button>
              <button @click="deleteMessage" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">削除</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  admin: Object,
  messages: Object,
  users: Array,
  channels: Array,
  filters: Object
})

const showDeleteModal = ref(false)
const messageToDelete = ref(null)

const searchMessages = () => {
  router.get(route('admin.messages.index'), props.filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const confirmDelete = (message) => {
  messageToDelete.value = message
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  messageToDelete.value = null
}

const deleteMessage = () => {
  if (messageToDelete.value) {
    router.delete(route('admin.messages.destroy', messageToDelete.value.id), {
      onSuccess: () => {
        closeDeleteModal()
      }
    })
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}
</script> 