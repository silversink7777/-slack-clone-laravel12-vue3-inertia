<template>
  <AdminLayout :admin="admin">
    <div class="space-y-6">
      <!-- ヘッダー -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">ユーザー管理</h1>
          <p class="text-gray-600">システム内の全ユーザーを管理できます</p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="refreshUsers"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            更新
          </button>
        </div>
      </div>

      <!-- 検索・フィルタ -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <form @submit.prevent="searchUsers" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- 検索 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">検索</label>
              <div class="relative">
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="名前またはメールアドレス"
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- オンライン状態フィルタ -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">オンライン状態</label>
              <select
                v-model="filters.online_status"
                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">すべて</option>
                <option value="online">オンライン</option>
                <option value="offline">オフライン</option>
              </select>
            </div>

            <!-- 検索ボタン -->
            <div class="flex items-end">
              <button
                type="submit"
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                検索
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- ユーザー一覧 -->
      <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            ユーザー一覧 ({{ users.total }}件)
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ユーザー
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ステータス
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  チャンネル数
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  メッセージ数
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  登録日
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <img
                        :src="user.profile_photo_url"
                        :alt="user.name"
                        class="h-10 w-10 rounded-full"
                      />
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                      <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div
                      :class="[
                        'h-2 w-2 rounded-full mr-2',
                        user.online_status?.online ? 'bg-green-400' : 'bg-gray-400'
                      ]"
                    ></div>
                    <span class="text-sm text-gray-900">
                      {{ user.online_status?.online ? 'オンライン' : 'オフライン' }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ user.channels?.length || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ user.messages_count || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(user.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <Link
                      :href="route('admin.users.show', user.id)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      詳細
                    </Link>
                    <Link
                      :href="route('admin.users.edit', user.id)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      編集
                    </Link>
                    <button
                      @click="confirmDelete(user)"
                      class="text-red-600 hover:text-red-900"
                    >
                      削除
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ページネーション -->
        <div v-if="users.links" class="px-6 py-3 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              {{ users.from }} - {{ users.to }} / {{ users.total }}件
            </div>
            <div class="flex space-x-2">
              <Link
                v-for="link in users.links"
                :key="link.label"
                v-if="link && link.url"
                :href="link.url"
                :class="[
                  'px-3 py-2 text-sm rounded-lg',
                  link.active
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                ]"
                v-html="link.label"
              />
              <span
                v-else-if="link"
                :class="[
                  'px-3 py-2 text-sm rounded-lg cursor-not-allowed',
                  'bg-gray-100 text-gray-400 border border-gray-200'
                ]"
                v-html="link.label"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 削除確認モーダル -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeDeleteModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mt-4">ユーザーを削除</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              「{{ userToDelete?.name }}」を削除しますか？この操作は取り消せません。
            </p>
          </div>
          <div class="flex justify-center space-x-4 mt-4">
            <button
              @click="closeDeleteModal"
              class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
            >
              キャンセル
            </button>
            <button
              @click="deleteUser"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
            >
              削除
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  admin: {
    type: Object,
    required: true
  },
  users: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const showDeleteModal = ref(false)
const userToDelete = ref(null)

const searchUsers = () => {
  router.get(route('admin.users.index'), props.filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const refreshUsers = () => {
  router.get(route('admin.users.index'))
}

const confirmDelete = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  userToDelete.value = null
}

const deleteUser = () => {
  if (userToDelete.value) {
    router.delete(route('admin.users.destroy', userToDelete.value.id), {
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