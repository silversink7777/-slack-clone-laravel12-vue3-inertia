<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\ChannelInvitation;
use App\Models\ChannelMember;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    protected $invitationRepository;
    protected $memberRepository;

    public function __construct(
        ChannelInvitationRepositoryInterface $invitationRepository,
        ChannelMemberRepositoryInterface $memberRepository
    ) {
        $this->invitationRepository = $invitationRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tbl_users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'invitation_id' => ['sometimes', 'string', 'exists:tbl_channel_invitations,id'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        // 招待IDが指定されている場合、招待を自動承認
        if (isset($input['invitation_id']) && !empty($input['invitation_id'])) {
            $this->processInvitation($user, $input['invitation_id']);
        }

        return $user;
    }

    /**
     * 招待を処理してチャンネルに自動参加
     */
    private function processInvitation(User $user, string $invitationId): void
    {
        try {
            $invitation = $this->invitationRepository->findValidById($invitationId);

            if (!$invitation) {
                Log::warning('Invalid invitation for auto-accept', [
                    'user_id' => $user->id,
                    'invitation_id' => $invitationId
                ]);
                return;
            }

            // メールアドレスによる招待の場合は、invitee_idを更新
            if ($invitation->invitee_email && !$invitation->invitee_id) {
                $this->invitationRepository->update($invitation, ['invitee_id' => $user->id]);
            }

            // 既にメンバーかチェック
            if ($this->memberRepository->isMember($invitation->channel_id, $user->id)) {
                Log::info('User already a member of the channel', [
                    'user_id' => $user->id,
                    'channel_id' => $invitation->channel_id
                ]);
                $this->invitationRepository->accept($invitation);
                return;
            }

            // チャンネルメンバーに追加
            $this->memberRepository->create([
                'channel_id' => $invitation->channel_id,
                'user_id' => $user->id,
                'role' => 'member',
            ]);

            // 招待を承認
            $this->invitationRepository->accept($invitation);

            Log::info('Invitation auto-accepted after registration', [
                'user_id' => $user->id,
                'invitation_id' => $invitationId,
                'channel_id' => $invitation->channel_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process invitation after registration', [
                'user_id' => $user->id,
                'invitation_id' => $invitationId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
