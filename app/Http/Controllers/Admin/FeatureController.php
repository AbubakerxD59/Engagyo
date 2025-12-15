<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct()
    {
        // permissions
        $this->middleware('permission:view_feature', ['only' => ['index']]);
        $this->middleware('permission:add_feature', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_feature', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_feature', ['only' => ['destroy']]);
        // permissions
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.features.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.features.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:features,key',
            'name' => 'required|string|max:255',
            'type' => 'required|in:boolean,numeric,unlimited',
            'default_value' => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'key' => $request->key,
            'name' => $request->name,
            'type' => $request->type,
            'default_value' => $request->default_value ?? ($request->type == 'boolean' ? 0 : null),
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
        ];

        $feature = Feature::create($data);

        if ($feature) {
            return redirect(route('admin.features.index'))->with('success', 'Feature added successfully!');
        } else {
            return back()->with('error', 'Something went wrong!');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $feature = Feature::findOrFail($id);
        return view('admin.features.edit', compact('feature'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $feature = Feature::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:features,key,' . $id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:boolean,numeric,unlimited',
            'default_value' => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'key' => $request->key,
            'name' => $request->name,
            'type' => $request->type,
            'default_value' => $request->default_value ?? ($request->type == 'boolean' ? 0 : null),
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? ($request->is_active ? true : false) : true,
        ];

        $feature->update($data);

        return redirect(route('admin.features.index'))->with('success', 'Feature updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully!'
        ]);
    }

    /**
     * DataTable for features
     */
    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        $order = end($data['order']);
        $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = Feature::query();
        $features = Feature::query();

        if (!empty($search)) {
            $features = $features->search($search);
        }
        $totalRecordswithFilter = clone $features;
        $features = $features->orderBy($orderby, $order['dir']);

        /*Set limit offset */
        $features = $features->offset($data['start']);
        $features = $features->limit($data['length']);

        $features = $features->get();
        foreach ($features as $k => $val) {
            $features[$k]['created'] = date('Y-m-d', strtotime($val->created_at));
            $features[$k]['action'] = view('admin.features.action')->with('feature', $val)->render();
            $features[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $features,
        ]);
    }
}

