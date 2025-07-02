<template>
  <AdminLayout :admin="admin">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-900">チャンネル詳細</h1>
        <Link :href="route('admin.channels.index')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">一覧へ戻る</Link>
      </div>
      <div class="bg-white rounded-lg shadow border border-gray-200 p-8 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 ">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">チャンネル名</label>
            <div class="text-lg font-semibold text-gray-900">{{ channel.name }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">作成日</label>
            <div class="text-gray-700">{{ formatDate(channel.created_at) }}</div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">メンバー</label>
          <div v-if="channel.members && channel.members.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名前</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メールアドレス</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="member in channel.members" :key="member.id">
                  <td class="px-4 py-2 text-gray-900">{{ member.user.name }}</td>
                  <td class="px-4 py-2 text-gray-500">{{ member.user.email }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-gray-400 text-sm">メンバーがいません</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">メッセージ数</label>
          <div class="text-2xl font-bold text-blue-700">{{ channel.messages.length }}</div>
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
  channel: Object
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}
</script> 