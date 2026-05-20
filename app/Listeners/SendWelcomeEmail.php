<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if (! $user->requiresEmailVerification()) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }
}
