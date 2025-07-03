<script setup>
import { computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    user: Object,
    showPrivateInfo: {
        type: Boolean,
        default: false
    }
});

const formatDate = (dateString) => {
    if (!dateString) return 'Not specified';
    return new Date(dateString).toLocaleDateString();
};

const formatAge = (birthDate) => {
    if (!birthDate) return null;
    const today = new Date();
    const birth = new Date(birthDate);
    const age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        return age - 1;
    }
    return age;
};

const hasSocialLinks = computed(() => {
    return props.user.social_links && Object.values(props.user.social_links).some(link => link);
});

const getSocialIcon = (platform) => {
    const icons = {
        twitter: '🐦',
        facebook: '📘',
        linkedin: '💼',
        github: '🐙',
        instagram: '📷'
    };
    return icons[platform] || '🔗';
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6">
            <!-- Profile Header -->
            <div class="flex items-center space-x-4 mb-6">
                <img 
                    :src="user.profile_photo_url" 
                    :alt="user.name" 
                    class="w-20 h-20 rounded-full object-cover border-4 border-gray-200"
                >
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ user.name }}</h2>
                    <p v-if="user.username" class="text-gray-600">@{{ user.username }}</p>
                    <p v-if="user.location" class="text-gray-500 text-sm">
                        📍 {{ user.location }}
                    </p>
                </div>
            </div>

            <!-- Bio -->
            <div v-if="user.bio" class="mb-6">
                <InputLabel value="About" class="text-sm font-medium text-gray-700" />
                <p class="mt-1 text-gray-600">{{ user.bio }}</p>
            </div>

            <!-- Profile Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>
                    
                    <div v-if="user.email && showPrivateInfo">
                        <InputLabel value="Email" class="text-sm font-medium text-gray-700" />
                        <p class="mt-1 text-gray-600">{{ user.email }}</p>
                    </div>

                    <div v-if="user.phone && showPrivateInfo">
                        <InputLabel value="Phone" class="text-sm font-medium text-gray-700" />
                        <p class="mt-1 text-gray-600">{{ user.phone }}</p>
                    </div>

                    <div v-if="user.birth_date">
                        <InputLabel value="Birth Date" class="text-sm font-medium text-gray-700" />
                        <p class="mt-1 text-gray-600">
                            {{ formatDate(user.birth_date) }}
                            <span v-if="formatAge(user.birth_date)" class="text-gray-500">
                                ({{ formatAge(user.birth_date) }} years old)
                            </span>
                        </p>
                    </div>

                    <div v-if="user.website">
                        <InputLabel value="Website" class="text-sm font-medium text-gray-700" />
                        <a 
                            :href="user.website" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="mt-1 text-blue-600 hover:text-blue-800 underline"
                        >
                            {{ user.website }}
                        </a>
                    </div>

                    <div v-if="user.timezone">
                        <InputLabel value="Timezone" class="text-sm font-medium text-gray-700" />
                        <p class="mt-1 text-gray-600">{{ user.timezone }}</p>
                    </div>

                    <div v-if="user.language">
                        <InputLabel value="Language" class="text-sm font-medium text-gray-700" />
                        <p class="mt-1 text-gray-600">{{ user.language }}</p>
                    </div>
                </div>

                <!-- Social Links -->
                <div v-if="hasSocialLinks" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Social Links</h3>
                    
                    <div class="space-y-3">
                        <div v-for="(link, platform) in user.social_links" :key="platform">
                            <div v-if="link" class="flex items-center space-x-2">
                                <span class="text-lg">{{ getSocialIcon(platform) }}</span>
                                <InputLabel :value="platform.charAt(0).toUpperCase() + platform.slice(1)" class="text-sm font-medium text-gray-700" />
                                <a 
                                    :href="link" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="text-blue-600 hover:text-blue-800 underline text-sm"
                                >
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Member Since -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500">
                    Member since {{ formatDate(user.created_at) }}
                </p>
                <p v-if="user.last_seen_at" class="text-sm text-gray-500">
                    Last seen {{ formatDate(user.last_seen_at) }}
                </p>
            </div>
        </div>
    </div>
</template> 