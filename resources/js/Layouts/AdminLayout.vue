<template>
  <div class="min-h-screen bg-gray-100">
    <!-- ヘッダー -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
            </div>
          </div>
          <div class="flex items-center">
            <div class="ml-3 relative">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">{{ admin?.name || '-' }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ admin?.role || '-' }}
                </span>
                <form @submit.prevent="logout" class="inline">
                  <button type="submit" class="text-sm text-red-600 hover:text-red-900">
                    ログアウト
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="flex">
      <!-- サイドバー -->
      <nav class="w-64 bg-white shadow-sm">
        <div class="h-full px-3 py-4 overflow-y-auto">
          <ul class="space-y-2">
            <li>
              <Link
                :href="route('admin.dashboard')"
                :class="[
                  'flex items-center p-2 text-base font-normal rounded-lg transition duration-75',
                  route().current('admin.dashboard')
                    ? 'text-white bg-blue-600'
                    : 'text-gray-900 hover:bg-gray-100'
                ]"
              >
                <svg class="w-6 h-6 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                  <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                </svg>
                <span class="ml-3">ダッシュボード</span>
              </Link>
            </li>
            <li>
              <Link
                :href="route('admin.users.index')"
                :class="[
                  'flex items-center p-2 text-base font-normal rounded-lg transition duration-75',
                  route().current('admin.users.*')
                    ? 'text-white bg-blue-600'
                    : 'text-gray-900 hover:bg-gray-100'
                ]"
              >
                <svg class="w-6 h-6 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="ml-3">ユーザー管理</span>
              </Link>
            </li>
            <li>
              <Link
                :href="route('admin.channels.index')"
                :class="[
                  'flex items-center p-2 text-base font-normal rounded-lg transition duration-75',
                  route().current('admin.channels.*')
                    ? 'text-white bg-blue-600'
                    : 'text-gray-900 hover:bg-gray-100'
                ]"
              >
                <svg class="w-6 h-6 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                  <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
                </svg>
                <span class="ml-3">チャンネル管理</span>
              </Link>
            </li>
            <li>
              <Link
                :href="route('admin.messages.index')"
                :class="[
                  'flex items-center p-2 text-base font-normal rounded-lg transition duration-75',
                  route().current('admin.messages.*')
                    ? 'text-white bg-blue-600'
                    : 'text-gray-900 hover:bg-gray-100'
                ]"
              >
                <svg class="w-6 h-6 transition duration-75" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                </svg>
                <span class="ml-3">メッセージ管理</span>
              </Link>
            </li>
          </ul>
        </div>
      </nav>

      <!-- メインコンテンツ -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  admin: {
    type: Object,
    required: false
  }
})

const logout = () => {
  router.post(route('admin.logout'))
}
</script> 