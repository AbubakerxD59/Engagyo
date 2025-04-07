<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    private $permission;
    public function __construct(Permission $permission)
    {
        // permissions
        $this->middleware('permission:view_permission', ['only' => ['index']]);
        // permissions

        $this->permission = $permission;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.permissions.index');
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
        //
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
        //    
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
        //
    }

    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        $order = end($data['order']);
        $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = $this->permission;
        $permissions = $this->permission;
        if (!empty($search)) {
            $permissions = $permissions->where(function ($query) use ($search) {
                $query->orWhere('label', 'like', '%' . $search . '%');
            });
        }
        $totalRecordswithFilter = clone $permissions;
        $permissions = $permissions->orderBy($orderby, $order['dir']);

        /*Set limit offset */
        $permissions = $permissions->offset($data['start']);
        $permissions = $permissions->limit($data['length']);

        $permissions = $permissions->get();
        foreach ($permissions as $k => $val) {
            $permissions[$k]['created'] = date('Y-m-d', strtotime($val->created_at));
            $permissions[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $permissions,
        ]);
    }
}
