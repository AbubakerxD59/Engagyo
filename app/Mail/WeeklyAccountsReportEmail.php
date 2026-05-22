<?php

namespace App\Mail;

use App\Models\User;
use App\Services\WeeklyAccountsReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyAccountsReportEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {
        $this->onQueue(config('mail_branding.queue', 'default'));
        $connection = config('mail_branding.queue_connection');
        if ($connection && $connection !== 'sync') {
            $this->onConnection($connection);
        }
    }

    public function build()
    {
        $data = app(WeeklyAccountsReportService::class)->buildReportForUser($this->user);

        return $this->subject('Your weekly connected accounts report — '.email_app_name())
            ->view('emails.weekly-accounts-report', $data);
    }
}
