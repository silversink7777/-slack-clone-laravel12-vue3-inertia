<template>
  <div class="bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">シンプルな招待テスト</h3>
    
    <div class="space-y-4">
      <div class="p-3 bg-green-100 rounded">
        <p class="text-green-700">✅ このコンポーネントは正常に表示されています</p>
      </div>
      
      <div class="p-3 bg-yellow-100 rounded">
        <p class="text-yellow-700">📊 招待データの状態:</p>
        <ul class="text-sm text-yellow-700 mt-2">
          <li>• 招待データ数: {{ invitations.length }}</li>
          <li>• 招待データが配列か: {{ Array.isArray(invitations) }}</li>
          <li>• 招待データの型: {{ typeof invitations }}</li>
        </ul>
      </div>
      
      <div v-if="invitations.length > 0" class="p-3 bg-blue-100 rounded">
        <p class="text-blue-700">🎉 招待データが見つかりました！</p>
        <div class="mt-2">
          <div v-for="(invitation, index) in invitations" :key="index" class="text-sm text-blue-700">
            • {{ invitation.inviter?.name }} さんから {{ invitation.channel?.name }} への招待
          </div>
        </div>
      </div>
      
      <div v-else class="p-3 bg-red-100 rounded">
        <p class="text-red-700">❌ 招待データが見つかりません</p>
        <p class="text-sm text-red-600 mt-1">データが空または正しく渡されていません</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  invitations: {
    type: Array,
    default: () => []
  }
})

console.log('SimpleInvitationTest mounted')
console.log('SimpleInvitationTest props:', props)
console.log('SimpleInvitationTest invitations:', props.invitations)
</script> 