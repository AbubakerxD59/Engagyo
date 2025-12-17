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
            // $features[$k]['action'] = view('admin.features.action')->with('feature', $val)->render();
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

