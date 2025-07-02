<template>
  <AdminLayout :admin="admin">
    <div class="max-w-xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">チャンネル編集</h1>
        <Link :href="route('admin.channels.show', channel.id)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">詳細へ戻る</Link>
      </div>
      <form @submit.prevent="submit" class="bg-white rounded-lg shadow border border-gray-200 p-8 space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">チャンネル名</label>
          <input id="name" v-model="form.name" type="text" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
          <div v-if="form.errors.name" class="text-red-600 text-xs mt-1">{{ form.errors.name }}</div>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="cancel" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">キャンセル</button>
          <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">保存</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  admin: Object,
  channel: Object
})

const form = useForm({
  name: props.channel?.name || ''
})

const submit = () => {
  if (!props.channel || !props.channel.id) {
    console.error('チャンネル情報が不正です')
    return
  }
  form.put(route('admin.channels.update', props.channel.id), {
    onSuccess: () => {
      router.visit(route('admin.channels.show', props.channel.id))
    }
  })
}

const cancel = () => {
  if (!props.channel || !props.channel.id) {
    console.error('チャンネル情報が不正です')
    return
  }
  router.visit(route('admin.channels.show', props.channel.id))
}
</script> 