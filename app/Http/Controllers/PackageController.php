<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Package;
use Illuminate\Http\Request;
use Stripe\Stripe;

class PackageController extends Controller
{
    private $package;
    private $feature;
    private $stripe;
    public function __construct(Package $package, Feature $feature)
    {
        // permissions
        $this->middleware('permission:view_package', ['only' => ['index']]);
        $this->middleware('permission:add_package', ['only' => ['create']]);
        $this->middleware('permission:edit_package', ['only' => ['edit']]);
        $this->middleware('permission:delete_package', ['only' => ['destroy']]);
        // permissions

        $this->package = $package;
        $this->feature = $feature;
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.packages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.packages.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'icon' => 'sometimes|file',
            'duration' => 'required',
            'date_type' => 'required',
            'price' => 'required',
        ]);
        // create stripe product
        $stripe_product = $this->stripe->products->create([
            'name' => $request->name,
            'active' => true,
        ]);
        if ($stripe_product->id) {
            // create stripe price
            $stripe_amount = $this->stripe->prices->create([
                'currency' => 'gbp',
                'active' => true,
                'product' => $stripe_product->id,
                'unit_amount_decimal' => $request->price * 100,
                'recurring' => [
                    'interval' => $request->date_type,
                    'interval_count' => $request->duration
                ]
            ]);
            if ($stripe_product->id && $stripe_amount->id) {
                $package = $this->package->create([
                    'name' => $request->name,
                    'icon' => $request->has('icon') ? saveImage($request->File('icon')) : '',
                    'duration' => $request->duration,
                    'date_type' => $request->date_type,
                    'price' => $request->price,
                    'stripe_product_id' => $stripe_product->id,
                    'stripe_price_id' => $stripe_amount->id,
                ]);
                if ($package) {
                    return redirect(route('packages.index'))->with('success', 'Package added Successfully!');
                } else {
                    return back()->with('error', 'Unable to add Package!');
                }
            }
        }
        return back()->with('error', 'Unable to add Package!');
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
        $package = $this->package->find($id);
        $features = $this->feature->get();
        return view('admin.packages.edit', compact('package', 'features'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required',
            'icon' => 'sometimes|file',
            'duration' => 'required',
            'date_type' => 'required',
            'price' => 'required',
        ]);
        $package = $this->package->find($id);
        if ($package) {
            // update stripe product
            $this->stripe->products->update($package->stripe_product_id, ['name' => $request->name]);
            // update stripe price
            $data = [
                'name' => $request->name,
                'duration' => $request->duration,
                'date_type' => $request->date_type,
                'price' => $request->price,
            ];
            if ($request->has('icon')) {
                $data['icon'] = saveImage($request->File('icon'));
            }
            $package->update($data);
            return redirect(route('packages.index'))->with('success', 'Package update successfully!');
        } else {
            return back()->with('error', 'Unable to update Package!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $package = $this->package->find($id);
        if ($package) {
            if ($package->delete()) {
                return back()->with('success', 'Package deleted successfully!');
            } else {
                return back()->with('error', 'Unable to delete Package!');
            }
        }
    }

    public function datatable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        // $order = end($data['order']);
        // $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = $this->package;
        $packages = new $this->package;

        if (!empty($search)) {
            $packages = $packages->search($search);
        }
        $totalRecordswithFilter = clone $packages;
        $packages->orderBy('id', 'ASC');

        /*Set limit offset */
        $packages = $packages->offset(intval($data['start']));
        $packages = $packages->limit(intval($data['length']));

        $packages = $packages->get();
        foreach ($packages as $k => $val) {
            $packages[$k]['icon_view'] = "<img src='{$val->icon}' class='rounded-pill' width='75px'>";
            $packages[$k]['name'] = "<a href=" . route('packages.edit', $val->id) . ">{$val->name}</a>";
            $packages[$k]['price'] = '£' . $val->price;
            $packages[$k]['time_duration'] = $val->time_duration();
            $packages[$k]['action'] = view('admin.packages.action')->with('package', $val)->render();
            $packages[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $packages,
        ]);
    }

    public function addFacility(Request $request)
    {
        $data = $request->validate([
            'package_id' => 'required'
        ]);
        $id = $request->package_id;
        $package = $this->package->find($id);
        if ($package) {
            $feature_ids = explode(',', $request->feature_ids);
            $package->features()->sync($feature_ids);
            $response = [
                'status' => true,
                'message' => 'Features added Successfully!'
            ];
        } else {
            $response = [
                'status' => false,
                'message' => 'Unable to add Features!'
            ];
        }
        return response()->json($response);
    }
}
