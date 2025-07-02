<template>
  <AdminLayout :admin="admin">
    <div class="space-y-6">
      <!-- ヘッダー -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">ユーザー詳細</h1>
          <p class="text-gray-600">ユーザーの詳細情報と履歴を確認できます</p>
        </div>
        <div class="flex space-x-3">
          <Link :href="route('admin.users.edit', user.id)" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">編集</Link>
          <Link :href="route('admin.users.index')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">一覧へ戻る</Link>
        </div>
      </div>

      <!-- 基本情報 -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex items-center space-x-6">
        <img :src="user.profile_photo_url" :alt="user.name" class="h-20 w-20 rounded-full border border-gray-300" />
        <div>
          <div class="text-xl font-bold text-gray-900">{{ user.name }}</div>
          <div class="text-gray-500">{{ user.email }}</div>
          <div class="mt-2 flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="user.online_status?.online ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
              {{ user.online_status?.online ? 'オンライン' : 'オフライン' }}
            </span>
            <span class="text-xs text-gray-400">登録日: {{ formatDate(user.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- 所属チャンネル -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">所属チャンネル</h2>
        <ul class="divide-y divide-gray-100">
          <li v-for="channel in user.channels" :key="channel.id" class="py-2 flex items-center">
            <span class="font-medium text-gray-800">#{{ channel.name }}</span>
          </li>
          <li v-if="!user.channels || user.channels.length === 0" class="text-gray-400">チャンネルなし</li>
        </ul>
      </div>

      <!-- 最近のメッセージ -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">最近のメッセージ</h2>
        <ul class="divide-y divide-gray-100">
          <li v-for="msg in user.messages" :key="msg.id" class="py-2">
            <div class="text-sm text-gray-800">{{ msg.content }}</div>
            <div class="text-xs text-gray-400">{{ formatDateTime(msg.created_at) }}</div>
          </li>
          <li v-if="!user.messages || user.messages.length === 0" class="text-gray-400">メッセージなし</li>
        </ul>
      </div>

      <!-- 招待履歴 -->
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">招待履歴</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">送信した招待</h3>
            <ul class="divide-y divide-gray-100">
              <li v-for="inv in user.sent_invitations" :key="inv.id" class="py-1 text-xs text-gray-700">
                {{ inv.channel?.name || 'チャンネル不明' }} → {{ inv.invitee_email || inv.invitee?.name || '未登録' }}
              </li>
              <li v-if="!user.sent_invitations || user.sent_invitations.length === 0" class="text-gray-400">なし</li>
            </ul>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">受信した招待</h3>
            <ul class="divide-y divide-gray-100">
              <li v-for="inv in user.received_invitations" :key="inv.id" class="py-1 text-xs text-gray-700">
                {{ inv.channel?.name || 'チャンネル不明' }} ← {{ inv.inviter?.name || '不明' }}
              </li>
              <li v-if="!user.received_invitations || user.received_invitations.length === 0" class="text-gray-400">なし</li>
            </ul>
          </div>
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
  user: Object,
  stats: Object
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}
const formatDateTime = (dateString) => {
  const d = new Date(dateString)
  return d.toLocaleDateString('ja-JP') + ' ' + d.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })
}
</script> 