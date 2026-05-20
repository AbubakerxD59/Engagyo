<?php

namespace App\Notifications;

use App\Http\Controllers\FrontEnd\EmailVerificationController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $verificationUrl,
    ) {
        $this->onQueue(config('mail_branding.queue', 'default'));
        $connection = config('mail_branding.queue_connection');
        if ($connection && $connection !== 'sync') {
            $this->onConnection($connection);
        }
    }

    public static function forUser(MustVerifyEmail $user): self
    {
        return new self(self::createVerificationUrl($user));
    }

    /**
     * Build signed URL while the web app is booted (before queue serializes this notification).
     */
    public static function createVerificationUrl(object $notifiable): string
    {
        self::ensureVerificationRouteExists();

        return URL::temporarySignedRoute(
            'frontend.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Queue workers may boot with a stale route cache; register the route if missing.
     */
    protected static function ensureVerificationRouteExists(): void
    {
        if (Route::has('frontend.verification.verify')) {
            return;
        }

        Route::middleware(['web', 'signed', 'throttle:6,1'])
            ->get('users/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->where(['id' => '[0-9]+', 'hash' => '[a-f0-9]+'])
            ->name('frontend.verification.verify');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your email — ' . email_app_name())
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}
