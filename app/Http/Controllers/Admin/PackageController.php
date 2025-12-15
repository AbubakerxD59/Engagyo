<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Feature;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct()
    {
        // permissions
        $this->middleware('permission:view_package', ['only' => ['index']]);
        $this->middleware('permission:add_package', ['only' => ['create']]);
        $this->middleware('permission:edit_package', ['only' => ['edit']]);
        $this->middleware('permission:delete_package', ['only' => ['destroy']]);
        // permissions
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
        $features = Feature::where('is_active', true)->get();
        return view('admin.packages.add', compact('features'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'date_type' => 'required|in:day,month,year',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'monthly_price' => $request->monthly_price ?? $request->price,
            'duration' => $request->duration,
            'date_type' => $request->date_type,
            'trial_days' => $request->trial_days ?? 0,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
            'stripe_product_id' => $request->stripe_product_id,
            'stripe_price_id' => $request->stripe_price_id,
        ];

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $icon = saveImage($request->file('icon'));
            $data['icon'] = $icon;
        }

        $package = Package::create($data);

        // Sync features
        if ($package && $request->has('features')) {
            $featuresData = [];
            foreach ($request->features as $featureId => $featureData) {
                if (isset($featureData['enabled'])) {
                    $featuresData[$featureId] = [
                        'limit_value' => isset($featureData['limit']) ? (int)$featureData['limit'] : null,
                        'is_enabled' => true,
                    ];
                }
            }
            $package->features()->sync($featuresData);
        }

        if ($package) {
            return redirect(route('admin.packages.index'))->with('success', 'Package added successfully!');
        } else {
            return back()->with('error', 'Unable to add Package!')->withInput();
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
        $package = Package::with('features')->findOrFail($id);
        $features = Feature::where('is_active', true)->get();
        return view('admin.packages.edit', compact('package', 'features'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'date_type' => 'required|in:day,month,year',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
        ]);

        $package = Package::findOrFail($id);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'monthly_price' => $request->monthly_price ?? $request->price,
            'duration' => $request->duration,
            'date_type' => $request->date_type,
            'trial_days' => $request->trial_days ?? 0,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
            'stripe_product_id' => $request->stripe_product_id,
            'stripe_price_id' => $request->stripe_price_id,
        ];

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $icon = saveImage($request->file('icon'));
            $data['icon'] = $icon;
        }

        if ($package->update($data)) {
            // Sync features
            $featuresData = [];
            if ($request->has('features')) {
                foreach ($request->features as $featureId => $featureData) {
                    if (isset($featureData['enabled'])) {
                        $featuresData[$featureId] = [
                            'limit_value' => isset($featureData['limit']) ? (int)$featureData['limit'] : null,
                            'is_enabled' => true,
                        ];
                    }
                }
            }
            $package->features()->sync($featuresData);
            
            return redirect(route('admin.packages.index'))->with('success', 'Package updated successfully!');
        } else {
            return back()->with('error', 'Unable to update Package!')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);
        
        if ($package->delete()) {
            return back()->with('success', 'Package deleted successfully!');
        } else {
            return back()->with('error', 'Unable to delete Package!');
        }
    }

    /**
     * DataTable for packages listing
     */
    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        
        $iTotalRecords = Package::query();
        $packages = Package::query();

        if (!empty($search)) {
            $packages = $packages->search($search);
        }
        
        $totalRecordswithFilter = clone $packages;
        $packages->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');

        /*Set limit offset */
        $packages = $packages->offset(intval($data['start']));
        $packages = $packages->limit(intval($data['length']));

        $packages = $packages->get();
        
        foreach ($packages as $k => $val) {
            $packages[$k]['icon_view'] = !empty($val->icon) ? "<img src='" . $val->icon . "' alt='Icon' width='50px'>" : '-';
            $packages[$k]['name'] = $val->name;
            $packages[$k]['time_duration'] = $val->time_duration();
            $packages[$k]['price'] = '$' . number_format($val->price, 2);
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
}

