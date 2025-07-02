<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
    invitation_id: '',
});

const invitationInfo = ref(null);

// URLパラメータから招待IDとメールアドレスを取得
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const invitationId = urlParams.get('invitation');
    const email = urlParams.get('email');
    
    if (invitationId) {
        form.invitation_id = invitationId;
        // 招待情報を取得
        fetchInvitationInfo(invitationId);
    }
    
    if (email) {
        form.email = email;
    }
});

// 招待情報を取得
const fetchInvitationInfo = async (invitationId) => {
    try {
        const response = await fetch(`/api/invitations/${invitationId}/info`);
        if (response.ok) {
            const data = await response.json();
            invitationInfo.value = data;
        }
    } catch (error) {
        console.error('Failed to fetch invitation info:', error);
    }
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <!-- 招待情報の表示 -->
        <div v-if="invitationInfo" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 text-sm font-medium">📺</span>
                    </div>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        {{ invitationInfo.channel.name }} チャンネルへの招待
                    </h3>
                    <p class="text-sm text-blue-600">
                        招待者: {{ invitationInfo.inviter.name }}
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                    :readonly="!!form.invitation_id"
                />
                <InputError class="mt-2" :message="form.errors.email" />
                <p v-if="form.invitation_id" class="mt-1 text-sm text-gray-500">
                    招待メールで指定されたメールアドレスです
                </p>
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                        <div class="ms-2">
                            I agree to the <a target="_blank" :href="route('terms.show')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Privacy Policy</a>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link :href="route('login')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ form.invitation_id ? 'アカウントを作成してチャンネルに参加' : 'Register' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
