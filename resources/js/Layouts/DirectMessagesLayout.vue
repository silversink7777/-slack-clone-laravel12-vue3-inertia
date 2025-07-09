<script setup>
import { Head } from '@inertiajs/vue3';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="flex h-screen bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
            <WorkspaceSidebar />
            <div class="flex-1 flex flex-col">
                <!-- メインコンテンツ -->
                <slot />
            </div>
        </div>
    </div>
</template> 