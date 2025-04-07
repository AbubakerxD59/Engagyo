<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    private $feature;
    public function __construct(Feature $feature)
    {
        // permissions
        $this->middleware('permission:view_feature', ['only' => ['index']]);
        $this->middleware('permission:add_feature', ['only' => ['create']]);
        $this->middleware('permission:edit_feature', ['only' => ['edit']]);
        $this->middleware('permission:delete_feature', ['only' => ['destroy']]);
        // permissions

        $this->feature = $feature;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required'
        ]);
        $feature = $this->feature->updateOrCreate([
            'name' => $request->name
        ], [
            'name' => $request->name
        ]);
        if ($feature) {
            if ($request->has('feature_id') && !empty($request->feature_id)) {
                $response = [
                    'status' => true,
                    'message' => 'Feature updated Successfully!'
                ];
            } else {
                $response = [
                    'status' => true,
                    'message' => 'Feature added Successfully!'
                ];
            }
        } else {
            $response = [
                'status' => false,
                'error' => 'Unable to add Feature!'
            ];
        }
        return response()->json($response);
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
        $feature = $this->feature->find($id);
        if ($feature) {
            $response = [
                'status' => true,
                'data' => $feature
            ];
        } else {
            $response = [
                'status' => false,
                'error' => 'Something went wrong!'
            ];
        }
        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $feature = $this->feature->find($id);
        if ($feature) {
            $feature->delete();
            return back()->with('success', 'Feature deleted Successfully!');
        } else {
            return back()->with('error', 'Unable to delete Feature!');
        }
    }

    public function datatable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        // $order = end($data['order']);
        // $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = $this->feature;
        $features = new $this->feature;

        if (!empty($search)) {
            $features = $features->search($search);
        }
        $totalRecordswithFilter = clone $features;
        $features->orderBy('id', 'ASC');

        /*Set limit offset */
        $features = $features->offset(intval($data['start']));
        $features = $features->limit(intval($data['length']));

        $features = $features->get();
        foreach ($features as $k => $val) {
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
