<template>
  <AdminLayout :admin="admin">
    <div v-if="user" class="max-w-xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">ユーザー編集</h1>
        <Link :href="route('admin.users.show', user.id)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">詳細へ戻る</Link>
      </div>
      <form @submit.prevent="submit" class="bg-white rounded-lg shadow border border-gray-200 p-8 space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
          <div v-if="form.errors.name" class="text-red-600 text-xs mt-1">{{ form.errors.name }}</div>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
          <div v-if="form.errors.email" class="text-red-600 text-xs mt-1">{{ form.errors.email }}</div>
        </div>
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="cancel"
            class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300"
          >
            キャンセル
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
          >
            保存
          </button>
        </div>
      </form>
    </div>
    <div v-else class="text-center text-gray-500 py-10">
      ユーザーデータが取得できませんでした。
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'

const props = defineProps({
  admin: Object,
  user: Object
})

// props.userの存在を確認してからuseFormを初期化
const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || ''
})

const submit = () => {
  if (!props.user || !props.user.id) {
    console.error('ユーザー情報が不正です')
    return
  }
  form.put(route('admin.users.update', props.user.id), {
    onSuccess: () => {
      router.visit(route('admin.users.show', props.user.id))
    }
  })
}

const cancel = () => {
  if (!props.user || !props.user.id) {
    console.error('ユーザー情報が不正です')
    return
  }
  router.visit(route('admin.users.show', props.user.id))
}
</script> 