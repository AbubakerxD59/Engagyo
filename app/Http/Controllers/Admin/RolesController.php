<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    private $role;
    private $permission;
    public function __construct(Role $role, Permission $permission)
    {
        // permissions
        $this->middleware('permission:view_role', ['only' => ['index']]);
        $this->middleware('permission:add_role', ['only' => ['create']]);
        $this->middleware('permission:edit_role', ['only' => ['edit']]);
        $this->middleware('permission:delete_role', ['only' => ['destroy']]);
        $this->middleware('permission:assign_role_permissions', ['only' => ['assignPermissions']]);
        // permissions

        $this->role = $role;
        $this->permission = $permission;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.roles.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles',
            'guard_name' => 'required',
        ]);
        $role = $this->role->create([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
            'created_by' => Auth::id(),
        ]);
        if (!empty($role)) {
            $response = [
                'success' => true,
                'message' => 'Role added successfully!',
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Unable to add Role!',
            ];
        }

        if ($response['success']) {
            return redirect(route('roles.index'))->with('success', $response['message']);
        } else {
            return back()->with('error', $response['message']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = $this->role->find($id);
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required',
            'guard_name' => 'required',
        ]);
        $role = $this->role::findById($id);
        if (!empty($role)) {
            $role->update([
                'name' => $request->name,
                'guard_name' => $request->guard_name,
            ]);
            $response = [
                'success' => true,
                'message' => 'Role updated successfully!',
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'unable to update Role!',
            ];
        }

        if ($response['success']) {
            return redirect(route('roles.index'))->with('success', $response['message']);
        } else {
            return back()->with('error', $response['message']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = $this->role->find($id);
        if ($role) {
            if ($role->delete()) {
                return redirect(route('roles.index'))->with('success', 'Role deleted successfully!');
            } else {
                return back()->with('error', 'Unable to delete Role!');
            }
        }
    }

    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        $order = end($data['order']);
        $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = $this->role;
        $roles = $this->role;

        if (!empty($search)) {
            $roles = $roles->search($search);
        }
        $totalRecordswithFilter = clone $roles;
        $roles->orderBy($orderby, $order['dir']);

        /*Set limit offset */
        $roles->offset(intval($data['start']));
        $roles->limit(intval($data['length']));

        $roles = $roles->get();
        foreach ($roles as $k => $val) {
            $roles[$k]['created'] = date('Y-m-d', strtotime($val->created_at));
            $roles[$k]['action'] = view('admin.roles.action')->with('role', $val)->with('permission_head', $this->permission->where('parent_id', 0)->get())->render();
            $roles[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $roles,
        ]);
    }

    public function assignPermissions(Request $request)
    {
        $role = $this->role->find($request->role_id);
        if ($request->has('permissions') && count($request->permissions) > 0) {
            $permissions = $this->permission->whereIn('id', $request->permissions)->pluck('id')->toArray();
            $role->syncPermissions($permissions);
        } else {
            $permissions = [];
            $role->syncPermissions($permissions);
        }
        $response = [
            'success' => true,
            'message' => 'Permissions updated!'
        ];
        if ($response['success']) {
            return back()->with('success', $response['message']);
        } else {
            return back()->with('error', $response['message']);
        }
    }
}
