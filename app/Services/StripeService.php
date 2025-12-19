<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Get Stripe client instance
     */
    public function getClient(): StripeClient
    {
        return $this->stripe;
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer(array $data)
    {
        try {
            return $this->stripe->customers->create([
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Create a checkout session
     */
    public function createCheckoutSession(array $data)
    {
        try {
            $sessionData = [
                'payment_method_types' => ['card'],
                'line_items' => $data['line_items'],
                'mode' => $data['mode'] ?? 'payment', // 'payment' or 'subscription'
                'success_url' => $data['success_url'],
                'cancel_url' => $data['cancel_url'],
                'metadata' => $data['metadata'] ?? [],
                'allow_promotion_codes' => $data['allow_promotion_codes'] ?? true,
            ];

            // Use customer ID if provided, otherwise use customer_email
            if (isset($data['customer'])) {
                $sessionData['customer'] = $data['customer'];
            } elseif (isset($data['customer_email'])) {
                $sessionData['customer_email'] = $data['customer_email'];
            }

            // Add subscription data for subscriptions (including trial days)
            if ($sessionData['mode'] === 'subscription' && isset($data['trial_period_days']) && $data['trial_period_days'] > 0) {
                $sessionData['subscription_data'] = [
                    'trial_period_days' => $data['trial_period_days'],
                ];
            }

            // For one-time payments with trial, we can use payment_intent_data to add metadata
            // Note: Stripe doesn't support trials for one-time payments, but we track it in metadata
            if ($sessionData['mode'] === 'payment' && isset($data['trial_period_days']) && $data['trial_period_days'] > 0) {
                if (!isset($sessionData['payment_intent_data'])) {
                    $sessionData['payment_intent_data'] = [];
                }
                $sessionData['payment_intent_data']['metadata'] = array_merge(
                    $sessionData['metadata'] ?? [],
                    ['trial_days' => $data['trial_period_days']]
                );
            }

            return $this->stripe->checkout->sessions->create($sessionData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create checkout session: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a checkout session
     */
    public function retrieveCheckoutSession(string $sessionId)
    {
        try {
            return $this->stripe->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve checkout session: ' . $e->getMessage());
        }
    }

    /**
     * Create a product in Stripe
     */
    public function createProduct(array $data)
    {
        try {
            return $this->stripe->products->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'active' => $data['active'] ?? true,
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe product: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a product in Stripe
     */
    public function retrieveProduct(string $productId)
    {
        try {
            return $this->stripe->products->retrieve($productId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe product: ' . $e->getMessage());
        }
    }

    /**
     * Update a product in Stripe
     */
    public function updateProduct(string $productId, array $data)
    {
        try {
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            
            if (isset($data['metadata'])) {
                $updateData['metadata'] = $data['metadata'];
            }
            
            if (isset($data['active'])) {
                $updateData['active'] = $data['active'];
            }

            if (empty($updateData)) {
                return null;
            }

            return $this->stripe->products->update($productId, $updateData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to update Stripe product: ' . $e->getMessage());
        }
    }

    /**
     * Archive a product in Stripe (sets active to false)
     */
    public function archiveProduct(string $productId)
    {
        try {
            return $this->stripe->products->update($productId, ['active' => false]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to archive Stripe product: ' . $e->getMessage());
        }
    }

    /**
     * Create a price in Stripe
     */
    public function createPrice(array $data)
    {
        try {
            $priceData = [
                'product' => $data['product_id'],
                'unit_amount' => $data['unit_amount'], // Amount in cents
                'currency' => $data['currency'] ?? 'gbp',
            ];

            // Add recurring if provided
            if (isset($data['recurring'])) {
                $priceData['recurring'] = $data['recurring'];
            }

            // Add metadata if provided
            if (isset($data['metadata'])) {
                $priceData['metadata'] = $data['metadata'];
            }

            return $this->stripe->prices->create($priceData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe price: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a price in Stripe
     */
    public function retrievePrice(string $priceId)
    {
        try {
            return $this->stripe->prices->retrieve($priceId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe price: ' . $e->getMessage());
        }
    }

    /**
     * Create a coupon
     */
    public function createCoupon(array $data)
    {
        try {
            $couponData = [
                'duration' => $data['duration'] ?? 'once',
                'name' => $data['name'],
            ];

            if (isset($data['amount_off'])) {
                $couponData['amount_off'] = $data['amount_off'];
                $couponData['currency'] = $data['currency'] ?? 'usd';
            } elseif (isset($data['percent_off'])) {
                $couponData['percent_off'] = $data['percent_off'];
            }

            // Add expiry date if provided (redeem_by is a Unix timestamp)
            if (isset($data['redeem_by'])) {
                $couponData['redeem_by'] = $data['redeem_by'];
            }

            return $this->stripe->coupons->create($couponData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe coupon: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a coupon
     */
    public function retrieveCoupon(string $couponId)
    {
        try {
            return $this->stripe->coupons->retrieve($couponId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe coupon: ' . $e->getMessage());
        }
    }

    /**
     * Delete a coupon
     */
    public function deleteCoupon(string $couponId)
    {
        try {
            return $this->stripe->coupons->delete($couponId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to delete Stripe coupon: ' . $e->getMessage());
        }
    }

    /**
     * Create a promotion code
     */
    public function createPromotionCode(array $data)
    {
        try {
            $promoCodeData = [
                'coupon' => $data['coupon_id'],
            ];

            // Add code if provided (optional, Stripe will generate one if not provided)
            if (isset($data['code'])) {
                $promoCodeData['code'] = $data['code'];
            }

            // Add expiry date if provided (expires_at is a Unix timestamp)
            if (isset($data['expires_at'])) {
                $promoCodeData['expires_at'] = $data['expires_at'];
            }

            // Add active status
            if (isset($data['active'])) {
                $promoCodeData['active'] = $data['active'];
            }

            return $this->stripe->promotionCodes->create($promoCodeData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe promotion code: ' . $e->getMessage());
        }
    }

    /**
     * Update a promotion code
     */
    public function updatePromotionCode(string $promotionCodeId, array $data)
    {
        try {
            $updateData = [];

            // Update active status
            if (isset($data['active'])) {
                $updateData['active'] = $data['active'];
            }

            // Update expiry date if provided
            if (isset($data['expires_at'])) {
                $updateData['expires_at'] = $data['expires_at'];
            }

            if (empty($updateData)) {
                return null;
            }

            return $this->stripe->promotionCodes->update($promotionCodeId, $updateData);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to update Stripe promotion code: ' . $e->getMessage());
        }
    }

    /**
     * Delete a promotion code
     */
    public function deletePromotionCode(string $promotionCodeId)
    {
        try {
            // Note: Stripe doesn't have a delete method for promotion codes
            // Instead, we deactivate them
            return $this->stripe->promotionCodes->update($promotionCodeId, ['active' => false]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to deactivate Stripe promotion code: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a customer
     */
    public function retrieveCustomer(string $customerId)
    {
        try {
            return $this->stripe->customers->retrieve($customerId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Update a customer
     */
    public function updateCustomer(string $customerId, array $data)
    {
        try {
            return $this->stripe->customers->update($customerId, $data);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to update Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a subscription
     */
    public function retrieveSubscription(string $subscriptionId)
    {
        try {
            return $this->stripe->subscriptions->retrieve($subscriptionId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update a subscription
     */
    public function updateSubscription(string $subscriptionId, array $data)
    {
        try {
            return $this->stripe->subscriptions->update($subscriptionId, $data);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to update Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately = false)
    {
        try {
            if ($immediately) {
                return $this->stripe->subscriptions->cancel($subscriptionId);
            } else {
                return $this->stripe->subscriptions->update($subscriptionId, [
                    'cancel_at_period_end' => true
                ]);
            }
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to cancel Stripe subscription: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve an invoice
     */
    public function retrieveInvoice(string $invoiceId)
    {
        try {
            return $this->stripe->invoices->retrieve($invoiceId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe invoice: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent
     */
    public function retrievePaymentIntent(string $paymentIntentId)
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to retrieve Stripe payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Construct webhook event from payload
     */
    public function constructWebhookEvent(string $payload, string $signature)
    {
        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            throw new \Exception('Webhook signature verification failed: ' . $e->getMessage());
        }
    }
}

