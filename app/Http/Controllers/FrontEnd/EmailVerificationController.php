<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        $user = Auth::guard('user')->user();

        if (! $user) {
            return redirect()->route('frontend.showLogin');
        }

        if (! $user->requiresEmailVerification()) {
            return redirect()->route('panel.schedule');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('panel.schedule');
        }

        $emailJustSent = $this->dispatchVerificationEmail($user);

        return view('frontend.auth.verify-email', [
            'user' => $user,
            'emailJustSent' => $emailJustSent,
        ]);
    }

    /**
     * Verify email via signed link (works when session expired).
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            if (! Auth::guard('user')->check()) {
                Auth::guard('user')->login($user);
            }

            return redirect()->route('panel.schedule')
                ->with('success', 'Your email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (! Auth::guard('user')->check()) {
            Auth::guard('user')->login($user);
        }

        $this->assignFreePackageIfNeeded($user);

        $packageId = session()->get('selected_package');
        if ($packageId) {
            $package = Package::where('id', $packageId)->active()->first();
            if ($package && $package->price > 0) {
                return redirect()->route('payment.checkout', $package->id)
                    ->with('success', 'Your email has been verified. Complete checkout to activate your plan.');
            }
        }

        return redirect()->route('panel.schedule')
            ->with('success', 'Your email has been verified. Welcome!');
    }

    public function send(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (! $user) {
            return redirect()->route('frontend.showLogin');
        }

        if (! $user->requiresEmailVerification() || $user->hasVerifiedEmail()) {
            return redirect()->route('panel.schedule');
        }

        $this->dispatchVerificationEmail($user, force: true);

        return back()->with('success', 'A new verification link has been sent to your email.');
    }

    /**
     * Queue/send verification email. Throttled per user to avoid duplicates on page refresh.
     */
    protected function dispatchVerificationEmail(User $user, bool $force = false): bool
    {
        $cacheKey = 'verification-email-sent:' . $user->id;

        if (! $force && Cache::has($cacheKey)) {
            return false;
        }

        try {
            $user->sendEmailVerificationNotification();
            Cache::put($cacheKey, true, now()->addMinutes(2));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch verification email: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);

            return false;
        }
    }

    protected function assignFreePackageIfNeeded(User $user): void
    {
        if ($user->package_id || $user->activeUserPackage) {
            return;
        }

        $freePackage = Package::free()->active()->first();
        if (! $freePackage) {
            return;
        }

        $user->assignFreePackage();
        $user->update(['package_id' => $freePackage->id]);
        Artisan::call('usage:sync', ['--user_id' => $user->id]);
    }
}
