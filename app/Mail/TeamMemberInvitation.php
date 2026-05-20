<?php

namespace App\Mail;

use App\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamMemberInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $teamMember;
    public $invitationToken;
    public $teamLeadName;
    public $invitationUrl;

    public function __construct(TeamMember $teamMember, string $invitationToken)
    {
        $this->teamMember = $teamMember;
        $this->invitationToken = $invitationToken;
        $this->teamLeadName = $teamMember->teamLead->full_name ?? $teamMember->teamLead->email;
        $this->invitationUrl = route('frontend.showRegister', ['token' => $invitationToken, 'email' => $teamMember->email]);

        $this->onQueue(config('mail_branding.queue', 'default'));
        $connection = config('mail_branding.queue_connection');
        if ($connection && $connection !== 'sync') {
            $this->onConnection($connection);
        }
    }

    public function build()
    {
        return $this->subject('You\'re invited to join a team on ' . email_app_name())
            ->view('emails.team-member-invitation', [
                'teamMember' => $this->teamMember,
                'invitationToken' => $this->invitationToken,
                'teamLeadName' => $this->teamLeadName,
                'invitationUrl' => $this->invitationUrl,
            ]);
    }
}
