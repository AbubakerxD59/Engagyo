<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromoCodeController extends Controller
{
    private $promoCode;
    private $stripeService;
    
    public function __construct(PromoCode $promoCode, StripeService $stripeService)
    {
        // permissions
        $this->middleware('permission:view_promocode', ['only' => ['index']]);
        $this->middleware('permission:add_promocode', ['only' => ['create']]);
        $this->middleware('permission:edit_promocode', ['only' => ['edit']]);
        $this->middleware('permission:delete_promocode', ['only' => ['destroy']]);
        // permissions

        $this->promoCode = $promoCode;
        $this->stripeService = $stripeService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.promo-code.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.promo-code.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:promo_codes,code',
            'duration' => 'required|integer|min:1',
            'discount_type' => 'required|in:fix,percent',
            'discount_amount' => 'required|numeric|min:0',
            'active' => 'nullable',
        ]);

        // Calculate expiry date (created_at + duration days)
        $expiresAt = now()->addDays($request->duration);
        $redeemBy = $expiresAt->timestamp;

        // Create Stripe coupon with expiry date
        try {
            $couponData = [
                'duration' => 'once',
                'name' => $request->name,
                'redeem_by' => $redeemBy,
            ];

            if ($request->discount_type == 'fix') {
                $couponData['amount_off'] = $request->discount_amount * 100;
                $couponData['currency'] = 'gbp';
            } else {
                $couponData['percent_off'] = $request->discount_amount;
            }

            $coupon = $this->stripeService->createCoupon($couponData);

            if (!$coupon) {
                return back()->with('error', 'Failed to create Stripe coupon!');
            }

            // Create Stripe promotion code
            $promotionCode = $this->stripeService->createPromotionCode([
                'coupon_id' => $coupon->id,
                'code' => $request->code,
                'expires_at' => $redeemBy,
                'active' => $request->has('active') && $request->active ? true : false,
            ]);

            if (!$promotionCode) {
                // If promotion code creation fails, delete the coupon
                try {
                    $this->stripeService->deleteCoupon($coupon->id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete Stripe coupon after promotion code creation failure: ' . $e->getMessage());
                }
                return back()->with('error', 'Failed to create Stripe promotion code!');
            }

        } catch (\Exception $e) {
            Log::error('Stripe sync error on promo code creation: ' . $e->getMessage());
            return back()->with('error', 'Failed to sync with Stripe: ' . $e->getMessage());
        }

        // Create promo code in database
        $promoCode = $this->promoCode->create([
            'name' => $request->name,
            'code' => $request->code,
            'duration' => $request->duration,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'status' => $request->has('active') && $request->active ? 1 : 0,
            'stripe_coupon_id' => $coupon->id,
            'stripe_promotion_code_id' => $promotionCode->id,
        ]);

        if ($promoCode) {
            return redirect(route('admin.promo-codes.index'))->with('success', 'Promo Code added Successfully!');
        } else {
            // If database creation fails, clean up Stripe resources
            try {
                $this->stripeService->deletePromotionCode($promotionCode->id);
                $this->stripeService->deleteCoupon($coupon->id);
            } catch (\Exception $e) {
                Log::error('Failed to clean up Stripe resources after database creation failure: ' . $e->getMessage());
            }
            return back()->with('error', 'Unable to add Promo Code!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $promo = $this->promoCode->find($id);
        return view('admin.promo-code.edit', compact('promo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:promo_codes,code,' . $id,
            'duration' => 'required|integer|min:1',
            'discount_type' => 'required|in:fix,percent',
            'discount_amount' => 'required|numeric|min:0',
            'active' => 'nullable',
        ]);

        $promo = $this->promoCode->find($id);
        if (!$promo) {
            return back()->with('error', 'Promo Code not found!');
        }

        // Calculate new expiry date based on updated duration
        // Expiry is always calculated from the original creation date + new duration
        $expiresAt = $promo->created_at->copy()->addDays($request->duration);
        $redeemBy = $expiresAt->timestamp;

        // Check if promo code has Stripe IDs (might be created before Stripe sync was added)
        $hasStripeIds = $promo->stripe_coupon_id && $promo->stripe_promotion_code_id;

        // Sync with Stripe - delete old coupon and promotion code, create new ones
        $oldCouponId = $promo->stripe_coupon_id;
        $oldPromotionCodeId = $promo->stripe_promotion_code_id;

        try {
            // Create new Stripe coupon with updated values
            $couponData = [
                'duration' => 'once',
                'name' => $request->name,
                'redeem_by' => $redeemBy,
            ];

            if ($request->discount_type == 'fix') {
                $couponData['amount_off'] = $request->discount_amount * 100;
                $couponData['currency'] = 'gbp';
            } else {
                $couponData['percent_off'] = $request->discount_amount;
            }

            $newCoupon = $this->stripeService->createCoupon($couponData);

            if (!$newCoupon) {
                return back()->with('error', 'Failed to create new Stripe coupon!');
            }

            // Create new Stripe promotion code
            $newPromotionCode = $this->stripeService->createPromotionCode([
                'coupon_id' => $newCoupon->id,
                'code' => $request->code,
                'expires_at' => $redeemBy,
                'active' => $request->has('active') && $request->active ? true : false,
            ]);

            if (!$newPromotionCode) {
                // If promotion code creation fails, delete the new coupon
                try {
                    $this->stripeService->deleteCoupon($newCoupon->id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete new Stripe coupon after promotion code creation failure: ' . $e->getMessage());
                }
                return back()->with('error', 'Failed to create new Stripe promotion code!');
            }

            // Delete old Stripe resources (only if they exist)
            if ($hasStripeIds) {
                if ($oldPromotionCodeId) {
                    try {
                        $this->stripeService->deletePromotionCode($oldPromotionCodeId);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old Stripe promotion code: ' . $e->getMessage());
                    }
                }

                if ($oldCouponId) {
                    try {
                        $this->stripeService->deleteCoupon($oldCouponId);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old Stripe coupon: ' . $e->getMessage());
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Stripe sync error on promo code update: ' . $e->getMessage());
            return back()->with('error', 'Failed to sync with Stripe: ' . $e->getMessage());
        }

        // Update promo code in database
        $updateData = [
            'name' => $request->name,
            'code' => $request->code,
            'duration' => $request->duration,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'status' => $request->has('active') && $request->active ? 1 : 0,
            'stripe_coupon_id' => $newCoupon->id,
            'stripe_promotion_code_id' => $newPromotionCode->id,
        ];

        if ($promo->update($updateData)) {
            return redirect(route('admin.promo-codes.index'))->with('success', 'Promo Code updated successfully!');
        } else {
            // If database update fails, clean up new Stripe resources
            try {
                $this->stripeService->deletePromotionCode($newPromotionCode->id);
                $this->stripeService->deleteCoupon($newCoupon->id);
            } catch (\Exception $e) {
                Log::error('Failed to clean up new Stripe resources after database update failure: ' . $e->getMessage());
            }
            return back()->with('error', 'Unable to update Promo Code!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promo = $this->promoCode->find($id);
        if (!$promo) {
            return back()->with('error', 'Promo Code not found!');
        }

        // Delete Stripe promotion code first
        if ($promo->stripe_promotion_code_id) {
            try {
                $this->stripeService->deletePromotionCode($promo->stripe_promotion_code_id);
            } catch (\Exception $e) {
                // Log error but continue with deletion
                Log::warning('Failed to delete Stripe promotion code: ' . $e->getMessage());
            }
        }

        // Delete Stripe coupon
        if ($promo->stripe_coupon_id) {
            try {
                $this->stripeService->deleteCoupon($promo->stripe_coupon_id);
            } catch (\Exception $e) {
                // Log error but continue with deletion
                Log::warning('Failed to delete Stripe coupon: ' . $e->getMessage());
            }
        }

        // Delete from database
        if ($promo->delete()) {
            return back()->with('success', 'Promo Code deleted successfully!');
        } else {
            return back()->with('error', 'Unable to delete Promo Code!');
        }
    }

    public function datatable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        $iTotalRecords = $this->promoCode;
        $promoCodes = new $this->promoCode;

        if (!empty($search)) {
            $promoCodes = $promoCodes->search($search);
        }
        $totalRecordswithFilter = clone $promoCodes;
        $promoCodes->orderBy('id', 'ASC');

        /*Set limit offset */
        $promoCodes = $promoCodes->offset(intval($data['start']));
        $promoCodes = $promoCodes->limit(intval($data['length']));

        $promoCodes = $promoCodes->get();
        foreach ($promoCodes as $k => $val) {
            $promoCodes[$k]['name'] = "<a href=" . route('admin.promo-codes.edit', $val->id) . ">{$val->name}</a>";
            $promoCodes[$k]['duration_day'] = $val->duration . ' day(s)';
            $promoCodes[$k]['discount_type'] = $val->discount_type == 'fix' ? 'Fixed Amount' : 'Percentage';
            $promoCodes[$k]['amount'] = $val->discount_type == 'fix' ? '£' . $val->discount_amount : $val->discount_amount . '%';
            $promoCodes[$k]['status_view'] = get_status_view($val->status);
            $promoCodes[$k]['action'] = view('admin.promo-code.action')->with('code', $val)->render();
            $promoCodes[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $promoCodes,
        ]);
    }
}
