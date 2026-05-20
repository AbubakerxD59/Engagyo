<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
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
        return $this->subject('Welcome to ' . email_app_name() . ' — you\'re all set!')
            ->view('emails.welcome', [
                'user' => $this->user,
                'loginUrl' => route('frontend.showLogin'),
                'panelUrl' => route('panel.schedule'),
            ]);
    }
}
