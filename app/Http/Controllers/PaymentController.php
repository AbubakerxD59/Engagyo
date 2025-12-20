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
            // Create or retrieve Stripe customer
            $customerId = $user->stripe_id;

            if (!$customerId) {
                // Create new Stripe customer
                $customer = $this->stripeService->createCustomer([
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $customerId = $customer->id;

                // Save customer ID to user
                $user->update(['stripe_id' => $customerId]);
            }

            // Prepare line items for Stripe
            $lineItems = [[
                'price' => $package->stripe_price_id,
                'quantity' => 1,
            ]];

            // Determine payment mode based on package
            // For now, we'll use 'payment' for one-time payments
            // If you want subscriptions, you can check package properties or add a field
            $mode = 'subscription'; // Default to one-time payment
            // You can add logic here to determine if it should be a subscription
            // For example: $mode = $package->is_recurring ? 'subscription' : 'payment';

            // Get trial days from package
            $trialDays = $package->trial_days ?? 0;

            // Create checkout session
            $sessionData = [
                'customer' => $customerId, // Use customer ID instead of email
                'line_items' => $lineItems,
                'mode' => $mode,
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'trial_days' => $trialDays, // Store trial days in metadata
                ],
                'allow_promotion_codes' => true,
            ];

            // Add trial days if package has them
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
                // Check if package is already activated by webhook
                $userId = $session->metadata->user_id ?? null;
                $packageId = $session->metadata->package_id ?? null;

                if ($userId && $packageId) {
                    $user = User::find($userId);
                    $package = Package::find($packageId);

                    if ($user && $package) {
                        // Check if subscription already exists (webhook may have processed it)
                        $subscription = UserSubscription::where('user_id', $user->id)
                            ->where('package_id', $package->id)
                            ->where('status', 'active')
                            ->first();

                        if (!$subscription) {
                            // Webhook hasn't processed yet, activate package as fallback
                            $this->activatePackageFromSession($session, $user, $package);
                        } else {
                            // Update user's package_id if not already set
                            if ($user->package_id != $package->id) {
                                $user->update(['package_id' => $package->id]);
                            }
                        }
                    }
                }

                // Redirect to user dashboard
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
        // Redirect to user panel if authenticated, otherwise to pricing
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

            // Check if event has already been processed (idempotency)
            if (StripeWebhookEvent::isProcessed($event->id)) {
                Log::info('Webhook event already processed', ['event_id' => $event->id, 'type' => $event->type]);
                return response()->json(['status' => 'already_processed'], 200);
            }

            // Handle different event types
            try {
                switch ($event->type) {
                    // Checkout & Payment Events
                    case 'checkout.session.completed':
                        $this->handleCheckoutSessionCompleted($event->data->object);
                        break;

                    case 'payment_intent.succeeded':
                        $this->handlePaymentIntentSucceeded($event->data->object);
                        break;

                    case 'payment_intent.payment_failed':
                        $this->handlePaymentFailed($event->data->object);
                        break;

                    // Subscription Events
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

                    // Invoice Events
                    case 'invoice.payment_succeeded':
                        $this->handleInvoicePaymentSucceeded($event->data->object);
                        break;

                    case 'invoice.payment_failed':
                        $this->handleInvoicePaymentFailed($event->data->object);
                        break;

                    case 'invoice.upcoming':
                        $this->handleInvoiceUpcoming($event->data->object);
                        break;

                    // Customer Events
                    case 'customer.created':
                        $this->handleCustomerCreated($event->data->object);
                        break;

                    case 'customer.updated':
                        $this->handleCustomerUpdated($event->data->object);
                        break;

                    default:
                        Log::info('Unhandled Stripe event: ' . $event->type);
                }

                // Mark event as processed
                StripeWebhookEvent::markAsProcessed(
                    $event->id,
                    $event->type,
                    json_decode($payload, true) ?? []
                );

                return response()->json(['status' => 'success'], 200);
            } catch (\Exception $e) {
                // Mark event as failed
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

            // Determine if this is a subscription or one-time payment
            $isSubscription = $session->mode === 'subscription';
            $subscriptionId = $session->subscription ?? null;
            $customerId = $session->customer ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            // Update user's Stripe customer ID if available
            if ($customerId && !$user->stripe_id) {
                $user->update(['stripe_id' => $customerId]);
            }

            // Get trial days from metadata or package
            $trialDays = (int)($session->metadata->trial_days ?? $package->trial_days ?? 0);

            // Calculate subscription dates
            $startsAt = Carbon::now();
            $endsAt = null;

            if ($isSubscription && $subscriptionId) {
                // For subscriptions, get the subscription details
                $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                // Check if subscription is in trial period
                if ($subscription->status === 'trialing' && $subscription->trial_end) {
                    $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                    // Trial end becomes the actual start of billing
                    $trialEnd = Carbon::createFromTimestamp($subscription->trial_end);
                    // After trial, subscription period starts
                    $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
                } else {
                    $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                    $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
                }
            } else {
                // For one-time payments, calculate based on package duration
                // If trial days exist, add them to the duration
                if ($trialDays > 0) {
                    $startsAt = Carbon::now();
                    // Trial period doesn't count towards package duration
                    // Package starts after trial ends
                    $packageStartDate = $startsAt->copy()->addDays($trialDays);

                    if ($package->date_type === 'day') {
                        $endsAt = $packageStartDate->copy()->addDays($package->duration);
                    } elseif ($package->date_type === 'month') {
                        $endsAt = $packageStartDate->copy()->addMonths($package->duration);
                    } elseif ($package->date_type === 'year') {
                        $endsAt = $packageStartDate->copy()->addYears($package->duration);
                    } elseif ($package->is_lifetime) {
                        $endsAt = null; // Lifetime package
                    }
                } else {
                    // No trial, normal calculation
                    if ($package->date_type === 'day') {
                        $endsAt = $startsAt->copy()->addDays($package->duration);
                    } elseif ($package->date_type === 'month') {
                        $endsAt = $startsAt->copy()->addMonths($package->duration);
                    } elseif ($package->date_type === 'year') {
                        $endsAt = $startsAt->copy()->addYears($package->duration);
                    } elseif ($package->is_lifetime) {
                        $endsAt = null; // Lifetime package
                    }
                }
            }

            // Deactivate previous active packages
            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create or update user package record
            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'is_active' => true,
                'assigned_at' => $startsAt,
                'expires_at' => $endsAt,
                'assigned_by' => null, // System assigned via payment
            ]);

            // Create or update subscription record
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

    /**
     * Activate package from checkout session (fallback method)
     */
    protected function activatePackageFromSession($session, $user, $package)
    {
        try {
            $isSubscription = $session->mode === 'subscription';
            $subscriptionId = $session->subscription ?? null;
            $customerId = $session->customer ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            // Update user's Stripe customer ID if available
            if ($customerId && !$user->stripe_id) {
                $user->update(['stripe_id' => $customerId]);
            }

            // Get trial days from metadata or package
            $trialDays = (int)($session->metadata->trial_days ?? $package->trial_days ?? 0);

            // Calculate subscription dates
            $startsAt = Carbon::now();
            $endsAt = null;

            if ($isSubscription && $subscriptionId) {
                try {
                    $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                    // Check if subscription is in trial period
                    if ($subscription->status === 'trialing' && $subscription->trial_end) {
                        $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                        $trialEnd = Carbon::createFromTimestamp($subscription->trial_end);
                        $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
                    } else {
                        $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                        $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve subscription details: ' . $e->getMessage());
                }
            } else {
                // For one-time payments, calculate based on package duration
                // If trial days exist, add them to the duration
                if ($trialDays > 0) {
                    $startsAt = Carbon::now();
                    $packageStartDate = $startsAt->copy()->addDays($trialDays);

                    if ($package->date_type === 'day') {
                        $endsAt = $packageStartDate->copy()->addDays($package->duration);
                    } elseif ($package->date_type === 'month') {
                        $endsAt = $packageStartDate->copy()->addMonths($package->duration);
                    } elseif ($package->date_type === 'year') {
                        $endsAt = $packageStartDate->copy()->addYears($package->duration);
                    } elseif ($package->is_lifetime) {
                        $endsAt = null; // Lifetime package
                    }
                } else {
                    // No trial, normal calculation
                    if ($package->date_type === 'day') {
                        $endsAt = $startsAt->copy()->addDays($package->duration);
                    } elseif ($package->date_type === 'month') {
                        $endsAt = $startsAt->copy()->addMonths($package->duration);
                    } elseif ($package->date_type === 'year') {
                        $endsAt = $startsAt->copy()->addYears($package->duration);
                    } elseif ($package->is_lifetime) {
                        $endsAt = null; // Lifetime package
                    }
                }
            }

            // Deactivate previous active packages
            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create or update user package record
            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'is_active' => true,
                'assigned_at' => $startsAt,
                'expires_at' => $endsAt,
                'assigned_by' => null, // System assigned via payment
            ]);

            // Create or update subscription record
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

            // Update user's package_id
            $user->update(['package_id' => $package->id]);

            // Update feature limits based on package features
            $this->updateUserFeatureLimits($user, $package);

            // Sync user usage with new package limits
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

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment intent succeeded', ['payment_intent_id' => $paymentIntent->id]);

        // Payment intent success is usually handled by checkout.session.completed
        // This is a fallback for direct payment intents
        if (isset($paymentIntent->metadata->user_id) && isset($paymentIntent->metadata->package_id)) {
            // Handle direct payment intent if needed
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

        // Update subscription status if it exists
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

            // Get package from subscription metadata or price
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

            // Handle trial period
            if ($subscription->status === 'trialing' && $subscription->trial_end) {
                $startsAt = Carbon::createFromTimestamp($subscription->trial_start ?? $subscription->created);
                $trialEnd = Carbon::createFromTimestamp($subscription->trial_end);
                $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
            } else {
                $startsAt = Carbon::createFromTimestamp($subscription->current_period_start);
                $endsAt = Carbon::createFromTimestamp($subscription->current_period_end);
            }

            // Get trial days from package or calculate from subscription
            $trialDays = $package->trial_days ?? 0;
            if ($subscription->trial_end && $subscription->trial_start) {
                $trialDays = (int)(($subscription->trial_end - $subscription->trial_start) / 86400);
            }

            // Deactivate previous active packages
            UserPackage::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create or update user package record
            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'is_active' => true,
                'assigned_at' => $startsAt,
                'expires_at' => $endsAt,
                'assigned_by' => null, // System assigned via payment
            ]);

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

            // Update user's package_id
            $user->update(['package_id' => $package->id]);

            // Update feature limits based on package features
            $this->updateUserFeatureLimits($user, $package);

            // Sync user usage with new package limits
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

            $updateData = [
                'status' => $this->mapSubscriptionStatus($subscription->status),
                'starts_at' => Carbon::createFromTimestamp($subscription->current_period_start),
                'ends_at' => Carbon::createFromTimestamp($subscription->current_period_end),
            ];

            // Handle cancellation
            if ($subscription->cancel_at_period_end) {
                $updateData['cancelled_at'] = Carbon::createFromTimestamp($subscription->current_period_end);
            } elseif ($subscription->status === 'canceled') {
                $updateData['cancelled_at'] = Carbon::now();
                $updateData['status'] = 'cancelled';
            }

            $userSubscription->update($updateData);

            // Update user's package if subscription is no longer active
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

                // Remove package from user
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

        // Send notification to user about trial ending
        // You can implement email notification here
        try {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if ($userSubscription && $userSubscription->user) {
                // TODO: Send email notification
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
                // One-time payment invoice
                return;
            }

            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

            if ($userSubscription) {
                // Update subscription period
                $subscription = $this->stripeService->retrieveSubscription($subscriptionId);

                $userSubscription->update([
                    'status' => 'active',
                    'starts_at' => Carbon::createFromTimestamp($subscription->current_period_start),
                    'ends_at' => Carbon::createFromTimestamp($subscription->current_period_end),
                    'amount_paid' => ($invoice->amount_paid ?? 0) / 100,
                    'currency' => $invoice->currency ?? 'gbp',
                ]);

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

                    // TODO: Send notification to user about failed payment
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

        // Send notification to user about upcoming invoice
        try {
            $subscriptionId = $invoice->subscription;

            if ($subscriptionId) {
                $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

                if ($userSubscription && $userSubscription->user) {
                    // TODO: Send email notification about upcoming invoice
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
            // Find user by email and update stripe_id
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

        // Sync customer data if needed
        // This is optional - you can update user data based on Stripe customer data
    }

    /**
     * Update user feature limits based on package features
     */
    protected function updateUserFeatureLimits(User $user, Package $package)
    {
        try {
            // Get all enabled features from the package
            $packageFeatures = $package->features()
                ->wherePivot('is_enabled', true)
                ->get();

            // Get current month start for period tracking
            $currentPeriodStart = Carbon::now()->startOfMonth()->format('Y-m-d');

            foreach ($packageFeatures as $feature) {
                $limitValue = $feature->pivot->limit_value;
                $isUnlimited = $feature->pivot->is_unlimited ?? false;

                // Create or update user feature usage record
                UserFeatureUsage::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'feature_id' => $feature->id,
                        'period_start' => $currentPeriodStart,
                        'is_archived' => false,
                    ],
                    [
                        'usage_count' => 0, // Reset usage count for new package
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

            // Archive old feature usage records for features not in new package
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
            // Don't throw - allow package activation to continue even if feature limits fail
        }
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
