<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, ClockIcon, MagnifyingGlassIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';

defineProps({
    activeChannel: Object,
});

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
    <div class="h-16 border-b border-gray-200 bg-white flex items-center justify-between px-6">
        <div>
            <button class="flex items-center font-bold">
                <span>{{ activeChannel?.name }}</span>
                <ChevronDownIcon class="h-5 w-5 ml-1" />
            </button>
        </div>
        <div class="flex items-center space-x-4">
            <ClockIcon class="h-6 w-6 text-gray-500" />
            <div class="relative">
                <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    placeholder="test 内を検索する"
                    class="bg-gray-100 rounded-md w-80 pl-10 pr-4 py-1.5 focus:ring-blue-500 focus:border-blue-500 border-transparent"
                >
            </div>
            
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
</template> 