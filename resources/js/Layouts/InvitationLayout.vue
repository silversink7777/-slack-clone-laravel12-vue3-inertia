<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import ChannelListSidebar from '@/Components/ChannelListSidebar.vue';

defineProps({
    title: String,
    channels: Array,
    'direct-messages': Array,
    manualOffline: Boolean,
});
const emit = defineEmits(['toggleManualOffline', 'selectChannel', 'newChannelAdded']);

const page = usePage();
const user = page.props.auth?.user;

const showUserMenu = ref(false);
const userMenuRef = ref(null);

const logout = () => {
    router.post(route('logout'));
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = () => {
    showUserMenu.value = false;
};

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        closeUserMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div>
    <Head :title="title" />

    <div class="flex h-screen bg-gray-100 text-gray-800">
      <WorkspaceSidebar />
      <ChannelListSidebar
        :channels="channels"
        :direct-messages="direct-messages"
        :manual-offline="manualOffline"
        @toggle-manual-offline="emit('toggleManualOffline', $event)"
        @select-channel="id => emit('selectChannel', id)"
        @new-channel-added="channel => emit('newChannelAdded', channel)"
      />
      <div class="flex-1 flex flex-col">
        <!-- ヘッダー部分（MainHeaderと同じデザイン） -->
        <div class="h-16 border-b border-gray-200 bg-white flex items-center justify-between px-6">
          <div>
            <button class="flex items-center font-bold">
              <span>{{ title }}</span>
              <ChevronDownIcon class="h-5 w-5 ml-1" />
            </button>
          </div>
          <div class="flex items-center space-x-4">
            <!-- User Menu -->
            <div ref="userMenuRef" class="relative">
              <button
                @click="toggleUserMenu"
                class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none"
              >
                <UserCircleIcon class="h-8 w-8" />
                <span class="text-sm font-medium">{{ user?.name }}</span>
                <ChevronDownIcon class="h-4 w-4" />
              </button>
              
              <!-- Dropdown Menu -->
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
              >
                <button
                  @click="logout"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  ログアウト
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- 招待通知エリア -->
        <slot name="notifications"></slot>
        
        <!-- メインコンテンツ -->
        <slot />
      </div>
    </div>
  </div>
</template> 