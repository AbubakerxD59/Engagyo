<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\UserPackage;
use App\Models\UserFeatureUsage;
use App\Models\Feature;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Initiate checkout for a package
     */
    public function checkout(Request $request, $packageId)
    {
        $request->validate([
            'promo_code' => 'nullable|string',
        ]);

        $package = Package::where('is_active', true)->findOrFail($packageId);

        if (!$package->stripe_price_id) {
            return back()->with('error', 'This package is not configured for payment. Please contact support.');
        }

        $user = Auth::guard('user')->user();

        try {
            $customerId = $user->stripe_id;

            if (!$customerId) {
                $customer = $this->stripeService->createCustomer([
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $customerId = $customer->id;
                $user->update(['stripe_id' => $customerId]);
            }

            $lineItems = [[
                'price' => $package->stripe_price_id,
                'quantity' => 1,
            ]];

            $mode = 'subscription';

            $trialDays = $package->trial_days ?? 0;

            $sessionData = [
                'customer' => $customerId,
                'line_items' => $lineItems,
                'mode' => $mode,
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'trial_days' => $trialDays,
                ],
                'allow_promotion_codes' => true,
            ];

            if ($trialDays > 0) {
                $sessionData['trial_period_days'] = $trialDays;
            }

            $session = $this->stripeService->createCheckoutSession($sessionData);

            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to initiate checkout. Please try again.');
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('frontend.pricing')->with('error', 'Invalid session.');
        }

        try {
            $session = $this->stripeService->retrieveCheckoutSession($sessionId);

            if ($session->payment_status === 'paid') {
                $userId = $session->metadata->user_id ?? null;
                $packageId = $session->metadata->package_id ?? null;

                if ($userId && $packageId) {
                    $user = User::find($userId);
                    $package = Package::find($packageId);

                    if ($user && $package) {
                        $subscription = UserSubscription::where('user_id', $user->id)
                            ->where('package_id', $package->id)
                            ->where('status', 'active')
                            ->first();

                        if (!$subscription) {
                            $this->activatePackageFromSession($session, $user, $package);
                        } else {
                            if ($user->package_id != $package->id) {
                                $user->update(['package_id' => $package->id]);
                            }
                        }
                    }
                }

                return redirect()->route('panel.schedule')->with('success', 'Payment successful! Your package has been activated.');
            }

            return redirect()->route('frontend.pricing')->with('error', 'Payment not completed.');
        } catch (\Exception $e) {
            Log::error('Payment success error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('frontend.pricing')->with('error', 'Failed to verify payment.');
        }
    }

    /**
     * Handle cancelled payment
     */
    public function cancel()
    {
        if (Auth::check()) {
            return redirect()->route('panel.schedule')->with('info', 'Payment was cancelled. You can try again anytime.');
        }
        return redirect()->route('frontend.pricing')->with('info', 'Payment was cancelled.');
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);

            if (StripeWebhookEvent::isProcessed($event->id)) {
                Log::info('Webhook event already processed', ['event_id' => $event->id, 'type' => $event->type]);
                return response()->json(['status' => 'already_processed'], 200);
            }

            try {
                switch ($event->type) {
                    case 'checkout.session.completed':
                        $this->handleCheckoutSessionCompleted($event->data->object);
                        break;

                    case 'payment_intent.succeeded':
                        $this->handlePaymentIntentSucceeded($event->data->object);
                        break;

                    case 'payment_intent.payment_failed':
                        $this->handlePaymentFailed($event->data->object);
                        break;

                    case 'customer.subscription.created':
                        $this->handleSubscriptionCreated($event->data->object);
                        break;

                    case 'customer.subscription.updated':
                        $this->handleSubscriptionUpdated($event->data->object);
                        break;

                    case 'customer.subscription.deleted':
                        $this->handleSubscriptionDeleted($event->data->object);
                        break;

                    case 'customer.subscription.trial_will_end':
                        $this->handleTrialWillEnd($event->data->object);
                        break;

                    case 'invoice.payment_succeeded':
                        $this->handleInvoicePaymentSucceeded($event->data->object);
                        break;

                    case 'invoice.payment_failed':
                        $this->handleInvoicePaymentFailed($event->data->object);
                        break;

                    case 'invoice.upcoming':
                        $this->handleInvoiceUpcoming($event->data->object);
                        break;

                    case 'customer.created':
                        $this->handleCustomerCreated($event->data->object);
                        break;

                    case 'customer.updated':
                        $this->handleCustomerUpdated($event->data->object);
                        break;

                    default:
                        Log::info('Unhandled Stripe event: ' . $event->type);
                }

                StripeWebhookEvent::markAsProcessed(
                    $event->id,
                    $event->type,
                    json_decode($payload, true) ?? []
                );

                return response()->json(['status' => 'success'], 200);
            } catch (\Exception $e) {
                StripeWebhookEvent::markAsFailed(
                    $event->id,
                    $event->type,
                    $e->getMessage(),
                    json_decode($payload, true) ?? []
                );
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle checkout session completed
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        Log::info('Checkout session completed', ['session_id' => $session->id]);

        try {
            $userId = $session->metadata->user_id ?? null;
            $packageId = $session->metadata->package_id ?? null;

            if (!$userId || !$packageId) {
                Log::warning('Missing metadata in checkout session', ['session_id' => $session->id]);
                return;
            }

            $user = User::find($userId);
            $package = Package::find($packageId);

            if (!$user || !$package) {
                Log::error('User or package not found', [
                    'user_id' => $userId,
                    'package_id' => $packageId
                ]);
                return;
            }

            $isSubscription = $session->mode === 'subscription';
            $subscriptionId = $session->subscription ?? null;
            $customerId = $session->customer ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            if ($customerId && !$user->stripe_id) {
                $user->update(['stripe_id' => $customerId]);
            }

            $trialDays = (int)($session->metadata->trial_days ?? $package->trial_days ?? 0);
            $startsAt = Carbon::now();
            $endsAt = null;

            if ($isSubscription && $subscriptionId) {
                $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                if ($subscription->status === 'trialing' && $subscription->trial_end) {
                    $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                    $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
                } else {
                    $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                    $endsAt = $this->calculateExpiryDate($package, $startsAt, 0);
                }
            } else {
                $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
            }

            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $userPackage = UserPackage::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'is_active' => true,
                    'assigned_at' => $startsAt,
                    'expires_at' => $endsAt,
                    'assigned_by' => null,
                ]
            );

            $subscription = UserSubscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'stripe_subscription_id' => $subscriptionId,
                    'stripe_customer_id' => $customerId,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'amount_paid' => $session->amount_total ? ($session->amount_total / 100) : $package->price / 100,
                    'currency' => $session->currency ?? 'gbp',
                    'metadata' => [
                        'session_id' => $session->id,
                        'mode' => $session->mode,
                        'trial_days' => $trialDays,
                    ],
                ]
            );

            // Update user's package_id
            $user->update(['package_id' => $package->id]);

            // Update feature limits based on package features
            $this->updateUserFeatureLimits($user, $package);

            // Sync user usage with new package limits
            try {
                Artisan::call('usage:sync', ['--user_id' => $user->id]);
                Log::info('User usage synced after package activation', [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to sync user usage after package activation: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            }

            Log::info('Package activated for user', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'user_package_id' => $userPackage->id,
                'subscription_id' => $subscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling checkout session completed: ' . $e->getMessage(), [
                'session_id' => $session->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function activatePackageFromSession($session, $user, $package)
    {
        try {
            $isSubscription = $session->mode === 'subscription';
            $subscriptionId = $session->subscription ?? null;
            $customerId = $session->customer ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            if ($customerId && !$user->stripe_id) {
                $user->update(['stripe_id' => $customerId]);
            }

            $trialDays = (int)($session->metadata->trial_days ?? $package->trial_days ?? 0);
            $startsAt = Carbon::now();
            $endsAt = null;

            if ($isSubscription && $subscriptionId) {
                try {
                    $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                    if ($subscription->status === 'trialing' && $subscription->trial_end) {
                        $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                        $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
                    } else {
                        $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                        $endsAt = $this->calculateExpiryDate($package, $startsAt, 0);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve subscription details: ' . $e->getMessage());
                    $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
                }
            } else {
                $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
            }

            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $userPackage = UserPackage::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'is_active' => true,
                    'assigned_at' => $startsAt,
                    'expires_at' => $endsAt,
                    'assigned_by' => null,
                ]
            );

            UserSubscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'stripe_subscription_id' => $subscriptionId,
                    'stripe_customer_id' => $customerId,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'amount_paid' => $session->amount_total ? ($session->amount_total / 100) : $package->price / 100,
                    'currency' => $session->currency ?? 'gbp',
                    'metadata' => [
                        'session_id' => $session->id,
                        'mode' => $session->mode,
                        'trial_days' => $trialDays,
                    ],
                ]
            );

            $user->update(['package_id' => $package->id]);
            $this->updateUserFeatureLimits($user, $package);

            try {
                Artisan::call('usage:sync', ['--user_id' => $user->id]);
                Log::info('User usage synced after package activation (fallback)', [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to sync user usage after package activation (fallback): ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            }

            Log::info('Package activated from success page (fallback)', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'user_package_id' => $userPackage->id,
                'session_id' => $session->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error activating package from session: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'package_id' => $package->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment intent succeeded', ['payment_intent_id' => $paymentIntent->id]);

        if (isset($paymentIntent->metadata->user_id) && isset($paymentIntent->metadata->package_id)) {
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_code' => $paymentIntent->last_payment_error->code ?? null,
            'failure_message' => $paymentIntent->last_payment_error->message ?? null,
        ]);

        if (isset($paymentIntent->metadata->subscription_id)) {
            $subscription = UserSubscription::where('stripe_subscription_id', $paymentIntent->metadata->subscription_id)
                ->first();

            if ($subscription) {
                $subscription->update(['status' => 'past_due']);
            }
        }
    }

    /**
     * Handle subscription created
     */
    protected function handleSubscriptionCreated($subscription)
    {
        Log::info('Subscription created', ['subscription_id' => $subscription->id]);

        try {
            $customerId = $subscription->customer;
            $user = User::where('stripe_id', $customerId)->first();

            if (!$user) {
                Log::warning('User not found for subscription', ['customer_id' => $customerId]);
                return;
            }

            $packageId = $subscription->metadata->package_id ?? null;

            if (!$packageId && isset($subscription->items->data[0]->price->metadata->package_id)) {
                $packageId = $subscription->items->data[0]->price->metadata->package_id;
            }

            if (!$packageId) {
                Log::warning('Package ID not found in subscription metadata');
                return;
            }

            $package = Package::find($packageId);
            if (!$package) {
                Log::error('Package not found', ['package_id' => $packageId]);
                return;
            }

            $trialDays = $package->trial_days ?? 0;
            if ($subscription->trial_end && $subscription->trial_start) {
                $trialDays = (int)(($subscription->trial_end - $subscription->trial_start) / 86400);
            }

            if ($subscription->status === 'trialing' && $subscription->trial_end) {
                $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                $endsAt = $this->calculateExpiryDate($package, $startsAt, $trialDays);
            } else {
                $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                $endsAt = $this->calculateExpiryDate($package, $startsAt, 0);
            }

            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $userPackage = UserPackage::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'is_active' => true,
                    'assigned_at' => $startsAt,
                    'expires_at' => $endsAt,
                    'assigned_by' => null,
                ]
            );

            UserSubscription::updateOrCreate(
                [
                    'stripe_subscription_id' => $subscription->id,
                ],
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'stripe_customer_id' => $customerId,
                    'status' => $subscription->status === 'active' || $subscription->status === 'trialing' ? 'active' : 'pending',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'amount_paid' => ($subscription->items->data[0]->price->unit_amount ?? 0) / 100,
                    'currency' => $subscription->currency ?? 'gbp',
                    'metadata' => [
                        'subscription_status' => $subscription->status,
                        'trial_days' => $trialDays,
                    ],
                ]
            );

            $user->update(['package_id' => $package->id]);
            $this->updateUserFeatureLimits($user, $package);

            try {
                Artisan::call('usage:sync', ['--user_id' => $user->id]);
                Log::info('User usage synced after subscription created', [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to sync user usage after subscription created: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ]);
            }

            Log::info('Subscription record created', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'user_package_id' => $userPackage->id,
                'subscription_id' => $subscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription created: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle subscription updated
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        Log::info('Subscription updated', ['subscription_id' => $subscription->id]);

        try {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if (!$userSubscription) {
                Log::warning('Subscription not found in database', ['subscription_id' => $subscription->id]);
                return;
            }

            $package = Package::find($userSubscription->package_id);
            $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);

            if ($package) {
                $endsAt = $this->calculateExpiryDate($package, $startsAt, 0);
            } else {
                Log::warning('Package not found for subscription update', [
                    'subscription_id' => $subscription->id,
                    'package_id' => $userSubscription->package_id
                ]);
                $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
            }

            $updateData = [
                'status' => $this->mapSubscriptionStatus($subscription->status),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];

            $userPackage = UserPackage::where('user_id', $userSubscription->user_id)
                ->where('package_id', $userSubscription->package_id)
                ->where('is_active', true)
                ->first();

            if ($userPackage && $package) {
                $userPackage->update([
                    'expires_at' => $endsAt
                ]);
            }

            if ($subscription->cancel_at_period_end) {
                $updateData['cancelled_at'] = Carbon::createFromTimestamp($subscription->current_period_end);
            } elseif ($subscription->status === 'canceled') {
                $updateData['cancelled_at'] = Carbon::now();
                $updateData['status'] = 'cancelled';
            }

            $userSubscription->update($updateData);

            if (in_array($subscription->status, ['canceled', 'unpaid', 'past_due'])) {
                $userSubscription->user->update(['package_id' => null]);
            }

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription updated: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle subscription deleted
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        Log::info('Subscription deleted', ['subscription_id' => $subscription->id]);

        try {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if ($userSubscription) {
                $userSubscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                ]);

                $userSubscription->user->update(['package_id' => null]);

                Log::info('Subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $userSubscription->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling subscription deleted: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle trial will end
     */
    protected function handleTrialWillEnd($subscription)
    {
        Log::info('Trial will end', ['subscription_id' => $subscription->id]);

        try {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if ($userSubscription && $userSubscription->user) {
                Log::info('Trial ending notification should be sent', [
                    'user_id' => $userSubscription->user_id,
                    'trial_end' => $subscription->trial_end
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling trial will end: ' . $e->getMessage());
        }
    }

    /**
     * Handle invoice payment succeeded
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        Log::info('Invoice payment succeeded', ['invoice_id' => $invoice->id]);

        try {
            $subscriptionId = $invoice->subscription;

            if (!$subscriptionId) {
                return;
            }

            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

            if ($userSubscription) {
                $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                $package = Package::find($userSubscription->package_id);
                if (!$package) {
                    Log::error('Package not found for subscription renewal', [
                        'subscription_id' => $subscriptionId,
                        'package_id' => $userSubscription->package_id
                    ]);
                    $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
                } else {
                    $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                    $endsAt = $this->calculateExpiryDate($package, $startsAt, 0);
                }

                $userSubscription->update([
                    'status' => 'active',
                    'starts_at' => Carbon::createFromTimestamp($subscription->current_period_start),
                    'ends_at' => $endsAt,
                    'amount_paid' => ($invoice->amount_paid ?? 0) / 100,
                    'currency' => $invoice->currency ?? 'gbp',
                ]);

                $userPackage = UserPackage::where('user_id', $userSubscription->user_id)
                    ->where('package_id', $userSubscription->package_id)
                    ->where('is_active', true)
                    ->first();

                if ($userPackage && $package) {
                    $userPackage->update([
                        'expires_at' => $endsAt
                    ]);
                }

                Log::info('Subscription renewed', [
                    'subscription_id' => $subscriptionId,
                    'user_id' => $userSubscription->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice payment succeeded: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle invoice payment failed
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);

        try {
            $subscriptionId = $invoice->subscription;

            if ($subscriptionId) {
                $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

                if ($userSubscription) {
                    $userSubscription->update(['status' => 'past_due']);

                    Log::info('Subscription marked as past due', [
                        'subscription_id' => $subscriptionId,
                        'user_id' => $userSubscription->user_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle invoice upcoming
     */
    protected function handleInvoiceUpcoming($invoice)
    {
        Log::info('Invoice upcoming', ['invoice_id' => $invoice->id]);

        try {
            $subscriptionId = $invoice->subscription;

            if ($subscriptionId) {
                $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

                if ($userSubscription && $userSubscription->user) {
                    Log::info('Upcoming invoice notification should be sent', [
                        'user_id' => $userSubscription->user_id,
                        'amount_due' => ($invoice->amount_due ?? 0) / 100
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice upcoming: ' . $e->getMessage());
        }
    }

    /**
     * Handle customer created
     */
    protected function handleCustomerCreated($customer)
    {
        Log::info('Customer created', ['customer_id' => $customer->id]);

        try {
            if (isset($customer->email)) {
                $user = User::where('email', $customer->email)->first();

                if ($user && !$user->stripe_id) {
                    $user->update(['stripe_id' => $customer->id]);
                    Log::info('User Stripe ID updated', [
                        'user_id' => $user->id,
                        'stripe_id' => $customer->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling customer created: ' . $e->getMessage());
        }
    }

    /**
     * Handle customer updated
     */
    protected function handleCustomerUpdated($customer)
    {
        Log::info('Customer updated', ['customer_id' => $customer->id]);
    }

    /**
     * Update user feature limits based on package features
     */
    protected function updateUserFeatureLimits(User $user, Package $package)
    {
        try {
            $packageFeatures = $package->features()
                ->wherePivot('is_enabled', true)
                ->get();

            $currentPeriodStart = Carbon::now()->startOfMonth()->format('Y-m-d');

            foreach ($packageFeatures as $feature) {
                $limitValue = $feature->pivot->limit_value;
                $isUnlimited = $feature->pivot->is_unlimited ?? false;

                UserFeatureUsage::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'feature_id' => $feature->id,
                        'period_start' => $currentPeriodStart,
                        'is_archived' => false,
                    ],
                    [
                        'usage_count' => 0,
                        'is_unlimited' => $isUnlimited,
                        'period_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
                    ]
                );

                Log::info('Feature limit updated for user', [
                    'user_id' => $user->id,
                    'feature_id' => $feature->id,
                    'feature_name' => $feature->name,
                    'limit_value' => $limitValue,
                    'is_unlimited' => $isUnlimited,
                ]);
            }

            $packageFeatureIds = $packageFeatures->pluck('id')->toArray();

            UserFeatureUsage::where('user_id', $user->id)
                ->where('is_archived', false)
                ->whereNotIn('feature_id', $packageFeatureIds)
                ->update([
                    'is_archived' => true,
                    'archived_at' => Carbon::now(),
                    'period_end' => DB::raw('COALESCE(period_end, CURDATE())'),
                ]);
        } catch (\Exception $e) {
            Log::error('Error updating user feature limits: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function calculateExpiryDate(Package $package, Carbon $startDate, int $trialDays = 0): ?Carbon
    {
        if ($package->is_lifetime) {
            return null;
        }

        $packageStartDate = $trialDays > 0
            ? $startDate->copy()->addDays($trialDays)
            : $startDate->copy();

        if (empty($package->duration) || empty($package->date_type)) {
            Log::warning('Package missing duration or date_type', [
                'package_id' => $package->id,
                'duration' => $package->duration,
                'date_type' => $package->date_type
            ]);
            return $packageStartDate->copy()->addDays(30);
        }

        switch (strtolower($package->date_type)) {
            case 'day':
            case 'days':
                $endsAt = $packageStartDate->copy()->addDays($package->duration);
                break;

            case 'month':
            case 'months':
                $endsAt = $packageStartDate->copy()->addMonths($package->duration);
                break;

            case 'year':
            case 'years':
                $endsAt = $packageStartDate->copy()->addYears($package->duration);
                break;

            default:
                Log::warning('Unknown package date_type', [
                    'package_id' => $package->id,
                    'date_type' => $package->date_type
                ]);
                $endsAt = $packageStartDate->copy()->addDays($package->duration);
                break;
        }

        return $endsAt;
    }

    /**
     * Map Stripe subscription status to our status
     */
    protected function mapSubscriptionStatus(string $stripeStatus): string
    {
        $statusMap = [
            'active' => 'active',
            'trialing' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            'unpaid' => 'cancelled',
            'incomplete' => 'pending',
            'incomplete_expired' => 'cancelled',
            'paused' => 'cancelled',
        ];

        return $statusMap[$stripeStatus] ?? 'pending';
    }
}
