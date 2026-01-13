<?php

namespace App\Mail;

use App\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamMemberInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $teamMember;
    public $invitationToken;
    public $teamLeadName;
    public $invitationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(TeamMember $teamMember, string $invitationToken)
    {
        $this->teamMember = $teamMember;
        $this->invitationToken = $invitationToken;
        $this->teamLeadName = $teamMember->teamLead->full_name ?? $teamMember->teamLead->email;
        $this->invitationUrl = route('frontend.showRegister', ['token' => $invitationToken, 'email' => $teamMember->email]);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Team Invitation - ' . env('APP_NAME', 'Engagyo'))
                    ->view('emails.team-member-invitation')
                    ->with([
                        'teamMember' => $this->teamMember,
                        'invitationToken' => $this->invitationToken,
                        'teamLeadName' => $this->teamLeadName,
                        'invitationUrl' => $this->invitationUrl,
                    ]);
    }
}

