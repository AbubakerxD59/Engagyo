<?php

namespace App\Services;

use App\Mail\PackageExpiredEmail;
use App\Mail\PackageExpiringSoonEmail;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class PackageExpirationEmailService
{
    /**
     * @return array{warning_sent: int, expired_sent: int, skipped: int}
     */
    public function processAll(): array
    {
        return [
            'warning_sent' => $this->processUpcomingExpirations(),
            'expired_sent' => $this->processExpiredPackages(),
            'skipped' => 0,
        ];
    }

    public function processUpcomingExpirations(): int
    {
        $warningDay = now()->addDays($this->warningDaysBefore())->startOfDay();
        $sent = 0;

        $this->eligiblePackagesQuery()
            ->whereNull('expiration_warning_sent_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                $warningDay,
                $warningDay->copy()->endOfDay(),
            ])
            ->with(['user.timezone', 'package'])
            ->orderBy('id')
            ->chunkById(100, function ($packages) use (&$sent) {
                foreach ($packages as $userPackage) {
                    if ($this->queueWarningEmail($userPackage)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function processExpiredPackages(): int
    {
        $sent = 0;

        $this->eligiblePackagesQuery()
            ->whereNull('expiration_expired_sent_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['user.timezone', 'package'])
            ->orderBy('id')
            ->chunkById(100, function ($packages) use (&$sent) {
                foreach ($packages as $userPackage) {
                    if ($this->queueExpiredEmail($userPackage)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function queueWarningEmail(UserPackage $userPackage): bool
    {
        if (! $this->canNotifyForPackage($userPackage)) {
            return false;
        }

        try {
            Mail::to($userPackage->user->email)->queue(
                new PackageExpiringSoonEmail($userPackage, $this->viewDataForPackage($userPackage))
            );
            $userPackage->forceFill(['expiration_warning_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to queue package expiring soon email', [
                'user_package_id' => $userPackage->id,
                'user_id' => $userPackage->user_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function queueExpiredEmail(UserPackage $userPackage): bool
    {
        if (! $this->canNotifyForPackage($userPackage)) {
            return false;
        }

        try {
            Mail::to($userPackage->user->email)->queue(
                new PackageExpiredEmail($userPackage, $this->viewDataForPackage($userPackage))
            );
            $userPackage->forceFill(['expiration_expired_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to queue package expired email', [
                'user_package_id' => $userPackage->id,
                'user_id' => $userPackage->user_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array{success: bool, message: string, email?: string, type?: string}
     */
    public function sendTestToUser(User $user, string $type = 'warning'): array
    {
        if (empty($user->email)) {
            return ['success' => false, 'message' => 'User has no email address.'];
        }

        $userPackage = UserPackage::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->with(['user.timezone', 'package'])
            ->orderByDesc('id')
            ->first();

        if (! $userPackage || ! $userPackage->package) {
            return [
                'success' => false,
                'message' => 'No active package with an expiration date found for this user.',
            ];
        }

        if ($userPackage->package->is_lifetime) {
            return [
                'success' => false,
                'message' => 'User has a lifetime package (no expiration emails).',
            ];
        }

        $viewData = $this->viewDataForPackage($userPackage);

        try {
            if ($type === 'expired') {
                Mail::to($user->email)->send(new PackageExpiredEmail($userPackage, $viewData));
            } else {
                Mail::to($user->email)->send(new PackageExpiringSoonEmail($userPackage, $viewData));
            }

            return [
                'success' => true,
                'message' => 'Package expiration test email sent.',
                'email' => $user->email,
                'type' => $type,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to send package expiration test email', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not send email: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function viewDataForPackage(UserPackage $userPackage): array
    {
        $userPackage->loadMissing(['user.timezone', 'package']);
        $user = $userPackage->user;
        $package = $userPackage->package;
        $expiresAt = $userPackage->expires_at;

        $timezone = TimezoneService::getUserTimezone($user);
        $expiresAtFormatted = $expiresAt
            ? Carbon::parse($expiresAt)->timezone($timezone)->format('l, F j, Y \a\t g:i A')
            : '';

        $daysRemaining = $expiresAt && $expiresAt->isFuture()
            ? (int) now()->diffInDays($expiresAt, false)
            : 0;

        return [
            'user' => $user,
            'packageName' => (string) ($package?->name ?? 'Your plan'),
            'expiresAtFormatted' => $expiresAtFormatted,
            'daysRemaining' => max(0, $daysRemaining),
            'warningDays' => $this->warningDaysBefore(),
            'planBillingUrl' => route('panel.plan.billing'),
            'loginUrl' => route('frontend.showLogin'),
        ];
    }

    private function eligiblePackagesQuery()
    {
        $roleId = Role::query()->where('name', 'User')->value('id');

        return UserPackage::query()
            ->active()
            ->whereHas('package', fn ($q) => $q->where('is_lifetime', false))
            ->whereHas('user', function ($q) use ($roleId) {
                $q->where('status', 1)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->whereNotNull('email_verified_at');

                if ($roleId) {
                    $q->whereHas('roles', fn ($r) => $r->where('roles.id', $roleId));
                }
            });
    }

    private function canNotifyForPackage(UserPackage $userPackage): bool
    {
        $userPackage->loadMissing(['user', 'package']);

        if (! $userPackage->package || $userPackage->package->is_lifetime) {
            return false;
        }

        if (empty($userPackage->expires_at)) {
            return false;
        }

        $user = $userPackage->user;

        return $user
            && ! empty($user->email)
            && $user->email_verified_at !== null
            && (int) $user->status === 1;
    }

    private function warningDaysBefore(): int
    {
        return max(1, (int) config('mail_branding.package_expiration_warning_days', 3));
    }
}
