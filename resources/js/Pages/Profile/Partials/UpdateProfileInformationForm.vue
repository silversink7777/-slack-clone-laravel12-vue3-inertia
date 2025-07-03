<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    username: props.user.username || '',
    email: props.user.email,
    bio: props.user.bio || '',
    location: props.user.location || '',
    website: props.user.website || '',
    phone: props.user.phone || '',
    birth_date: props.user.birth_date || '',
    timezone: props.user.timezone || 'UTC',
    language: props.user.language || 'en',
    social_links: {
        twitter: props.user.social_links?.twitter || '',
        facebook: props.user.social_links?.facebook || '',
        linkedin: props.user.social_links?.linkedin || '',
        github: props.user.social_links?.github || '',
        instagram: props.user.social_links?.instagram || '',
    },
    is_public_profile: props.user.is_public_profile !== false,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Profile Information
        </template>

        <template #description>
            Update your account's profile information and email address.
        </template>

        <template #form>
            <!-- Profile Photo -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Photo" />

                <!-- Current Profile Photo -->
                <div v-show="! photoPreview" class="mt-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full size-20 object-cover">
                </div>

                <!-- New Profile Photo Preview -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full size-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <SecondaryButton class="mt-2 me-2" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo
                </SecondaryButton>

                <SecondaryButton
                    v-if="user.profile_photo_path"
                    type="button"
                    class="mt-2"
                    @click.prevent="deletePhoto"
                >
                    Remove Photo
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Username -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="username" value="Username" />
                <TextInput
                    id="username"
                    v-model="form.username"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="username"
                />
                <InputError :message="form.errors.username" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
                    <p class="text-sm mt-2">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-green-600">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="bio" value="Bio" />
                <textarea
                    id="bio"
                    v-model="form.bio"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="3"
                    placeholder="Tell us about yourself..."
                />
                <InputError :message="form.errors.bio" class="mt-2" />
            </div>

            <!-- Location -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="location" value="Location" />
                <TextInput
                    id="location"
                    v-model="form.location"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="City, Country"
                />
                <InputError :message="form.errors.location" class="mt-2" />
            </div>

            <!-- Website -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="website" value="Website" />
                <TextInput
                    id="website"
                    v-model="form.website"
                    type="url"
                    class="mt-1 block w-full"
                    placeholder="https://example.com"
                />
                <InputError :message="form.errors.website" class="mt-2" />
            </div>

            <!-- Phone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="phone" value="Phone" />
                <TextInput
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    class="mt-1 block w-full"
                    placeholder="+1 (555) 123-4567"
                />
                <InputError :message="form.errors.phone" class="mt-2" />
            </div>

            <!-- Birth Date -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="birth_date" value="Birth Date" />
                <TextInput
                    id="birth_date"
                    v-model="form.birth_date"
                    type="date"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.birth_date" class="mt-2" />
            </div>

            <!-- Timezone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="timezone" value="Timezone" />
                <select
                    id="timezone"
                    v-model="form.timezone"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                    <option value="Europe/London">London</option>
                    <option value="Europe/Paris">Paris</option>
                    <option value="Asia/Tokyo">Tokyo</option>
                    <option value="Asia/Shanghai">Shanghai</option>
                </select>
                <InputError :message="form.errors.timezone" class="mt-2" />
            </div>

            <!-- Language -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="language" value="Language" />
                <select
                    id="language"
                    v-model="form.language"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="en">English</option>
                    <option value="ja">日本語</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                </select>
                <InputError :message="form.errors.language" class="mt-2" />
            </div>

            <!-- Social Links -->
            <div class="col-span-6">
                <InputLabel value="Social Links" />
                <div class="mt-2 space-y-4">
                    <div>
                        <InputLabel for="twitter" value="Twitter" class="text-sm" />
                        <TextInput
                            id="twitter"
                            v-model="form.social_links.twitter"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://twitter.com/username"
                        />
                        <InputError :message="form.errors['social_links.twitter']" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="facebook" value="Facebook" class="text-sm" />
                        <TextInput
                            id="facebook"
                            v-model="form.social_links.facebook"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://facebook.com/username"
                        />
                        <InputError :message="form.errors['social_links.facebook']" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="linkedin" value="LinkedIn" class="text-sm" />
                        <TextInput
                            id="linkedin"
                            v-model="form.social_links.linkedin"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://linkedin.com/in/username"
                        />
                        <InputError :message="form.errors['social_links.linkedin']" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="github" value="GitHub" class="text-sm" />
                        <TextInput
                            id="github"
                            v-model="form.social_links.github"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://github.com/username"
                        />
                        <InputError :message="form.errors['social_links.github']" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="instagram" value="Instagram" class="text-sm" />
                        <TextInput
                            id="instagram"
                            v-model="form.social_links.instagram"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://instagram.com/username"
                        />
                        <InputError :message="form.errors['social_links.instagram']" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Public Profile -->
            <div class="col-span-6 sm:col-span-4">
                <div class="flex items-center">
                    <Checkbox
                        id="is_public_profile"
                        v-model:checked="form.is_public_profile"
                        name="is_public_profile"
                    />
                    <InputLabel for="is_public_profile" value="Make profile public" class="ms-2" />
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    Allow other users to view your profile information.
                </p>
                <InputError :message="form.errors.is_public_profile" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
