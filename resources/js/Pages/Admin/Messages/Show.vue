<template>
  <AdminLayout :admin="admin">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-900">メッセージ詳細</h1>
        <Link :href="route('admin.messages.index')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">一覧へ戻る</Link>
      </div>
      <div class="bg-white rounded-lg shadow border border-gray-200 p-8 space-y-8">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">内容</label>
          <div class="text-lg font-semibold text-gray-900 break-words">{{ message.content }}</div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ユーザー</label>
            <div class="text-gray-900">{{ message.user?.name || '-' }}</div>
            <div class="text-gray-500 text-sm">{{ message.user?.email || '-' }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">チャンネル</label>
            <div class="text-gray-900">{{ message.channel?.name || '-' }}</div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">作成日</label>
          <div class="text-gray-700">{{ formatDate(message.created_at) }}</div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  admin: Object,
  message: Object
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}
</script> 