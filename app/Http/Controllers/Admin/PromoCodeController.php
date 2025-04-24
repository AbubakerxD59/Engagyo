<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    private $promoCode;
    private $stripe;
    public function __construct(PromoCode $promoCode)
    {
        // permissions
        $this->middleware('permission:view_promocode', ['only' => ['index']]);
        $this->middleware('permission:add_promocode', ['only' => ['create']]);
        $this->middleware('permission:edit_promocode', ['only' => ['edit']]);
        $this->middleware('permission:delete_promocode', ['only' => ['destroy']]);
        // permissions

        $this->promoCode = $promoCode;
        // $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
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
            'name' => 'required',
            'code' => 'required',
            'duration' => 'required',
            'discount_type' => 'required',
            'discount_amount' => 'required',
            'active' => 'required',
        ]);
        // create stripe coupon
        if ($request->discount_type == 'fix') {
            $coupon = $this->stripe->coupons->create([
                "amount_off" => $request->discount_amount * 100,
                "currency" => "gbp",
                "duration" => "once",
                "name" => $request->name,
            ]);
        } else {
            $coupon = $this->stripe->coupons->create([
                "percent_off" => $request->discount_amount,
                "duration" => "once",
                "name" => $request->name,
            ]);
        }
        if ($coupon) {
            $package = $this->promoCode->create([
                'name' => $request->name,
                'code' => $request->code,
                'duration' => $request->duration,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount,
                'status' => $request->active,
                'stripe_coupon_id' => $coupon->id,
            ]);
            if ($package) {
                return redirect(route('promo-code.index'))->with('success', 'Promo Code added Successfully!');
            } else {
                return back()->with('error', 'Unable to add Package!');
            }
        } else {
            return back()->with('error', 'Unable to add Package!');
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
            'name' => 'required',
            'code' => 'required',
            'duration' => 'required',
            'discount_type' => 'required',
            'discount_amount' => 'required',
            'active' => 'required',
        ]);
        $promo = $this->promoCode->find($id);
        if ($promo) {
            $data = [
                'name' => $request->name,
                'code' => $request->code,
                'duration' => $request->duration,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount,
                'status' => $request->active,
            ];
            $promo->update($data);
            return redirect(route('promo-code.index'))->with('success', 'Promo Code update successfully!');
        } else {
            return back()->with('error', 'Unable to update Promo Code!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promo = $this->promoCode->find($id);
        // delete stripe coupon
        $this->stripe->coupons->delete($promo->stripe_coupon_id);
        if ($promo) {
            if ($promo->delete()) {
                return back()->with('success', 'Promo Code deleted successfully!');
            } else {
                return back()->with('error', 'Unable to delete Promo Code!');
            }
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
            $promoCodes[$k]['name'] = "<a href=" . route('promo-code.edit', $val->id) . ">{$val->name}</a>";
            $promoCodes[$k]['duration_day'] = $val->duration . ' day(s)';
            $promoCodes[$k]['amount'] = $val->discount_type == 'fix' ? '£' . $val->discount_amount : $val->discount_amount . ' %';
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
