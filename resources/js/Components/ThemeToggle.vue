<template>
    <div class="relative">
        <button
            @click="showDropdown = !showDropdown"
            class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
            :disabled="isLoading"
        >
            <SunIcon v-if="isDark" class="h-5 w-5 text-yellow-500" />
            <MoonIcon v-else class="h-5 w-5 text-gray-600 dark:text-gray-400" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ getThemeLabel(theme) }}
            </span>
            <ChevronDownIcon class="h-4 w-4 text-gray-500 dark:text-gray-400" />
        </button>

        <!-- ドロップダウンメニュー -->
        <div
            v-if="showDropdown"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
        >
            <div class="py-1">
                <button
                    @click="selectTheme('light')"
                    class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'light' }"
                >
                    <SunIcon class="h-4 w-4" />
                    <span>ライト</span>
                    <CheckIcon v-if="theme === 'light'" class="h-4 w-4 ml-auto" />
                </button>
                
                <button
                    @click="selectTheme('dark')"
                    class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'dark' }"
                >
                    <MoonIcon class="h-4 w-4" />
                    <span>ダーク</span>
                    <CheckIcon v-if="theme === 'dark'" class="h-4 w-4 ml-auto" />
                </button>
                
                <button
                    @click="selectTheme('system')"
                    class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'system' }"
                >
                    <ComputerDesktopIcon class="h-4 w-4" />
                    <span>システム</span>
                    <CheckIcon v-if="theme === 'system'" class="h-4 w-4 ml-auto" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { SunIcon, MoonIcon, ChevronDownIcon, CheckIcon, ComputerDesktopIcon } from '@heroicons/vue/24/outline'
import { useTheme } from '@/Composables/useTheme'

const { theme, isDark, isLoading, updateTheme } = useTheme()

const showDropdown = ref(false)

const getThemeLabel = (currentTheme) => {
    switch (currentTheme) {
        case 'light':
            return 'ライト'
        case 'dark':
            return 'ダーク'
        case 'system':
            return 'システム'
        default:
            return 'システム'
    }
}

const selectTheme = async (newTheme) => {
    try {
        await updateTheme(newTheme)
        showDropdown.value = false
    } catch (error) {
        console.error('Failed to update theme:', error)
        alert('テーマの更新に失敗しました')
    }
}

// ドロップダウンの外側をクリックした時に閉じる
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script> 