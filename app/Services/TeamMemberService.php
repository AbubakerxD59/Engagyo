<?php

namespace App\Services;

use App\Models\User;
use App\Models\TeamMember;
use App\Models\TeamMemberMenu;
use App\Models\TeamMemberFeatureLimit;
use App\Models\TeamMemberAccount;
use App\Mail\TeamMemberInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TeamMemberService
{
    /**
     * Invite a team member by email
     */
    public function inviteTeamMember(User $teamLead, string $email, array $menus = [], array $featureLimits = [], array $accounts = []): array
    {
        // Validate email doesn't belong to team lead
        if ($teamLead->email === $email) {
            return ['success' => false, 'message' => 'You cannot invite yourself.'];
        }

        // Check if user is already a team member
        $existingMember = TeamMember::where('team_lead_id', $teamLead->id)
            ->where('email', $email)
            ->first();

        if ($existingMember) {
            return ['success' => false, 'message' => 'This user is already invited or is a team member.'];
        }

        DB::beginTransaction();
        try {
            // Check if user exists
            $user = User::where('email', $email)->first();
            
            $teamMember = TeamMember::create([
                'team_lead_id' => $teamLead->id,
                'member_id' => $user ? $user->id : null,
                'email' => $email,
                'status' => $user ? 'active' : 'pending',
                'invited_at' => now(),
                'joined_at' => $user ? now() : null,
            ]);

            $invitationToken = $teamMember->generateInvitationToken();

            // Assign menus if provided
            if (!empty($menus)) {
                // Delete existing menus
                $teamMember->menus()->delete();
                
                // Add new menus
                foreach ($menus as $menuId) {
                    TeamMemberMenu::create([
                        'team_member_id' => $teamMember->id,
                        'menu_id' => $menuId,
                    ]);
                }
            }

            // Set feature limits if provided
            if (!empty($featureLimits)) {
                foreach ($featureLimits as $featureId => $limitData) {
                    TeamMemberFeatureLimit::updateOrCreate(
                        [
                            'team_member_id' => $teamMember->id,
                            'feature_id' => $featureId,
                        ],
                        [
                            'limit_value' => $limitData['limit_value'] ?? null,
                            'is_unlimited' => $limitData['is_unlimited'] ?? false,
                        ]
                    );
                }
            }

            // Assign accounts if provided
            if (!empty($accounts)) {
                foreach ($accounts as $account) {
                    TeamMemberAccount::create([
                        'team_member_id' => $teamMember->id,
                        'account_type' => $account['type'],
                        'account_id' => $account['id'],
                    ]);
                }
            }

            // Send invitation email if user doesn't exist
            if (!$user) {
                try {
                    Mail::to($email)->send(new TeamMemberInvitation($teamMember, $invitationToken));
                } catch (\Exception $mailException) {
                    // Log the error but don't fail the invitation
                    Log::error('Failed to send team member invitation email: ' . $mailException->getMessage());
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $user ? 'Team member added successfully.' : 'Invitation sent successfully.',
                'team_member' => $teamMember,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Failed to invite team member: ' . $e->getMessage()];
        }
    }

    /**
     * Update team member menus
     */
    public function updateMenus(TeamMember $teamMember, array $menuIds): bool
    {
        try {
            // Delete existing menus
            $teamMember->menus()->delete();
            
            // Add new menus
            foreach ($menuIds as $menuId) {
                TeamMemberMenu::create([
                    'team_member_id' => $teamMember->id,
                    'menu_id' => $menuId,
                ]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update team member feature limits
     */
    public function updateFeatureLimits(TeamMember $teamMember, array $featureLimits): bool
    {
        try {
            foreach ($featureLimits as $featureId => $limitData) {
                TeamMemberFeatureLimit::updateOrCreate(
                    [
                        'team_member_id' => $teamMember->id,
                        'feature_id' => $featureId,
                    ],
                    [
                        'limit_value' => $limitData['limit_value'] ?? null,
                        'is_unlimited' => $limitData['is_unlimited'] ?? false,
                    ]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update team member account access
     */
    public function updateAccountAccess(TeamMember $teamMember, array $accounts): bool
    {
        try {
            // Delete existing accounts
            $teamMember->accounts()->delete();

            // Add new accounts
            foreach ($accounts as $account) {
                TeamMemberAccount::create([
                    'team_member_id' => $teamMember->id,
                    'account_type' => $account['type'],
                    'account_id' => $account['id'],
                ]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove team member
     */
    public function removeTeamMember(TeamMember $teamMember): bool
    {
        try {
            $teamMember->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(string $token, User $user): array
    {
        $teamMember = TeamMember::where('invitation_token', $token)
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->first();

        if (!$teamMember) {
            return ['success' => false, 'message' => 'Invalid invitation token.'];
        }

        try {
            $teamMember->update([
                'member_id' => $user->id,
                'status' => 'active',
                'joined_at' => now(),
                'invitation_token' => null,
            ]);

            return ['success' => true, 'message' => 'Invitation accepted successfully.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to accept invitation.'];
        }
    }
}

