<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Feature;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        // permissions
        $this->middleware('permission:view_package', ['only' => ['index']]);
        $this->middleware('permission:add_package', ['only' => ['create']]);
        $this->middleware('permission:edit_package', ['only' => ['edit']]);
        $this->middleware('permission:delete_package', ['only' => ['destroy']]);
        // permissions

        $this->stripeService = $stripeService;
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
            'duration' => 'required|integer|min:1',
            'date_type' => 'required|in:day,month,year',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_lifetime' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'date_type' => $request->date_type,
            'trial_days' => $request->trial_days ?? 0,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
            'is_lifetime' => $request->has('is_lifetime') ? ($request->is_lifetime ? true : false) : false,
            'stripe_product_id' => $request->stripe_product_id,
            'stripe_price_id' => $request->stripe_price_id,
        ];

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $icon = saveImage($request->file('icon'));
            $data['icon'] = $icon;
        }

        // Create Stripe product and price if package has a price
        if ($data['price'] > 0 && !$request->stripe_product_id) {
            try {
                // Create Stripe product
                $stripeProduct = $this->stripeService->createProduct([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'metadata' => [
                        'package_id' => null, // Will be updated after package creation
                    ],
                    'active' => $data['is_active'] ?? true,
                ]);

                // Create Stripe price
                $stripePrice = $this->stripeService->createPrice([
                    'product_id' => $stripeProduct->id,
                    'unit_amount' => $data['price'], // Price is already in cents
                    'currency' => 'gbp',
                    'metadata' => [
                        'package_id' => null, // Will be updated after package creation
                    ],
                ]);

                $data['stripe_product_id'] = $stripeProduct->id;
                $data['stripe_price_id'] = $stripePrice->id;
            } catch (\Exception $e) {
                Log::error('Failed to create Stripe product/price: ' . $e->getMessage());
                return back()->with('error', 'Failed to create Stripe product. Please try again.')->withInput();
            }
        }

        $package = Package::create($data);

        // Update Stripe product metadata with package ID
        if ($package->stripe_product_id) {
            try {
                $this->stripeService->updateProduct($package->stripe_product_id, [
                    'metadata' => [
                        'package_id' => $package->id,
                    ],
                ]);

                // Update price metadata if exists
                if ($package->stripe_price_id) {
                    // Note: Stripe prices are immutable, so we can't update metadata
                    // But we can update the product which is linked to the price
                }
            } catch (\Exception $e) {
                Log::warning('Failed to update Stripe product metadata: ' . $e->getMessage());
            }
        }

        // Sync features
        if ($package && $request->has('features')) {
            $featuresData = [];
            foreach ($request->features as $featureId => $featureData) {
                if (isset($featureData['enabled'])) {
                    $isUnlimited = isset($featureData['unlimited']) && $featureData['unlimited'] == '1';
                    $featuresData[$featureId] = [
                        'limit_value' => $isUnlimited ? null : (isset($featureData['limit']) ? (int)$featureData['limit'] : null),
                        'is_enabled' => true,
                        'is_unlimited' => $isUnlimited,
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
            'duration' => 'required|integer|min:1',
            'date_type' => 'required|in:day,month,year',
            'trial_days' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_lifetime' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
        ]);

        $package = Package::findOrFail($id);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'date_type' => $request->date_type,
            'trial_days' => $request->trial_days ?? 0,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
            'is_lifetime' => $request->has('is_lifetime') ? ($request->is_lifetime ? true : false) : false,
            // Don't set stripe IDs from request - they will be set during Stripe sync below
        ];

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $icon = saveImage($request->file('icon'));
            $data['icon'] = $icon;
        }

        // Track if price, duration, or date_type changed (affects Stripe price)
        $priceChanged = $package->price != $data['price'];
        $durationChanged = $package->duration != $data['duration'];
        $dateTypeChanged = $package->date_type != $data['date_type'];
        $needsNewPrice = $priceChanged || $durationChanged || $dateTypeChanged;

        // Sync with Stripe
        if ($data['price'] > 0) {
            try {
                $stripeProductId = $package->stripe_product_id;

                // Create Stripe product if it doesn't exist
                if (!$stripeProductId) {
                    $stripeProduct = $this->stripeService->createProduct([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'metadata' => [
                            'package_id' => $package->id,
                        ],
                        'active' => $data['is_active'] ?? true,
                    ]);
                    $stripeProductId = $stripeProduct->id;
                    $data['stripe_product_id'] = $stripeProductId;
                } else {
                    // Update Stripe product (preserve existing product ID)
                    $data['stripe_product_id'] = $stripeProductId;
                    $this->stripeService->updateProduct($stripeProductId, [
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'active' => $data['is_active'] ?? true,
                        'metadata' => [
                            'package_id' => $package->id,
                        ],
                    ]);
                }

                // Determine if recurring or one-time payment
                $recurring = null;
                if ($data['date_type'] !== 'day' || $data['duration'] > 1) {
                    // Only day with duration 1 is one-time, others are recurring
                    $interval = $data['date_type'] === 'day' ? 'day' : ($data['date_type'] === 'month' ? 'month' : 'year');
                    $intervalCount = $data['duration'];
                    $recurring = [
                        'interval' => $interval,
                        'interval_count' => $intervalCount,
                    ];
                }

                // Create new price if price/duration/date_type changed OR if no price exists (prices are immutable in Stripe)
                if ($needsNewPrice && $stripeProductId) {
                    // Create a new price (old price remains in Stripe but won't be used)
                    $stripePrice = $this->stripeService->createPrice([
                        'product_id' => $stripeProductId,
                        'unit_amount' => (int)$data['price'], // Price is already in cents (matching store method)
                        'currency' => 'gbp',
                        'recurring' => $recurring,
                        'metadata' => [
                            'package_id' => $package->id,
                            'trial_days' => $data['trial_days'] ?? 0,
                        ],
                    ]);
                    $data['stripe_price_id'] = $stripePrice->id;
                } elseif (!$package->stripe_price_id && $stripeProductId) {
                    // Create price if it doesn't exist
                    $stripePrice = $this->stripeService->createPrice([
                        'product_id' => $stripeProductId,
                        'unit_amount' => (int)$data['price'], // Price is already in cents (matching store method)
                        'currency' => 'gbp',
                        'recurring' => $recurring,
                        'metadata' => [
                            'package_id' => $package->id,
                            'trial_days' => $data['trial_days'] ?? 0,
                        ],
                    ]);
                    $data['stripe_price_id'] = $stripePrice->id;
                } else {
                    // Preserve existing IDs if price/duration/date_type didn't change
                    // Product ID is already preserved above, just ensure price ID is preserved
                    if (!$needsNewPrice) {
                        $data['stripe_product_id'] = $package->stripe_product_id;
                        $data['stripe_price_id'] = $package->stripe_price_id;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync package with Stripe: ' . $e->getMessage(), [
                    'package_id' => $package->id,
                    'trace' => $e->getTraceAsString()
                ]);
                return back()->with('error', 'Failed to sync with Stripe. Please try again.')->withInput();
            }
        } else {
            // If price is 0, archive the Stripe product if it exists
            if ($package->stripe_product_id) {
                try {
                    $this->stripeService->archiveProduct($package->stripe_product_id);
                    $data['stripe_product_id'] = null;
                    $data['stripe_price_id'] = null;
                } catch (\Exception $e) {
                    Log::warning('Failed to archive Stripe product when price set to 0: ' . $e->getMessage());
                }
            }
        }

        if ($package->update($data)) {
            // Sync features
            $featuresData = [];
            if ($request->has('features')) {
                foreach ($request->features as $featureId => $featureData) {
                    if (isset($featureData['enabled'])) {
                        $isUnlimited = isset($featureData['unlimited']) && $featureData['unlimited'] == '1';
                        $featuresData[$featureId] = [
                            'limit_value' => $isUnlimited ? null : (isset($featureData['limit']) ? (int)$featureData['limit'] : null),
                            'is_enabled' => true,
                            'is_unlimited' => $isUnlimited,
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

        // Archive Stripe product if it exists
        if ($package->stripe_product_id) {
            try {
                $this->stripeService->archiveProduct($package->stripe_product_id);
                Log::info('Stripe product archived', [
                    'package_id' => $package->id,
                    'stripe_product_id' => $package->stripe_product_id
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to archive Stripe product: ' . $e->getMessage(), [
                    'package_id' => $package->id,
                    'stripe_product_id' => $package->stripe_product_id
                ]);
                // Continue with deletion even if Stripe archive fails
            }
        }

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
