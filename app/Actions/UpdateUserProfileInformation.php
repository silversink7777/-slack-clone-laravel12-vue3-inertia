<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:tbl_users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', Rule::unique('tbl_users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'language' => ['nullable', 'string', 'max:10'],
            'social_links' => ['nullable', 'array'],
            'social_links.twitter' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.linkedin' => ['nullable', 'url'],
            'social_links.github' => ['nullable', 'url'],
            'social_links.instagram' => ['nullable', 'url'],
            'is_public_profile' => ['boolean'],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        $user->forceFill([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'bio' => $input['bio'] ?? null,
            'location' => $input['location'] ?? null,
            'website' => $input['website'] ?? null,
            'phone' => $input['phone'] ?? null,
            'birth_date' => $input['birth_date'] ?? null,
            'timezone' => $input['timezone'] ?? 'UTC',
            'language' => $input['language'] ?? 'en',
            'social_links' => $input['social_links'] ?? null,
            'is_public_profile' => $input['is_public_profile'] ?? true,
        ])->save();
    }
}
