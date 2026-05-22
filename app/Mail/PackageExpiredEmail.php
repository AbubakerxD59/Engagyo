<?php

namespace App\Mail;

use App\Models\UserPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PackageExpiredEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $packageEmailData
     */
    public function __construct(
        public UserPackage $userPackage,
        public array $packageEmailData = []
    ) {
        $this->onQueue(config('mail_branding.queue', 'default'));
        $connection = config('mail_branding.queue_connection');
        if ($connection && $connection !== 'sync') {
            $this->onConnection($connection);
        }
    }

    public function build()
    {
        return $this->subject('Your '.($this->packageEmailData['packageName'] ?? 'plan').' has expired — renew to continue — '.email_app_name())
            ->view('emails.package-expired', $this->packageEmailData);
    }
}
