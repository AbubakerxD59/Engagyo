<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        // permissions
        $this->middleware('permission:view_user', ['only' => ['index']]);
        $this->middleware('permission:add_user', ['only' => ['create']]);
        $this->middleware('permission:edit_user', ['only' => ['edit', 'showInfo']]);
        $this->middleware('permission:delete_user', ['only' => ['destroy']]);
        // permissions
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::get();
        return view('admin.users.add', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|email|max:250|unique:users',
            'password' => 'required|min:4|confirmed',
            'role' => 'required',
        ]);
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
        ]);
        if (!empty($user)) {
            $role = Role::find($request->role);
            if (!empty($role)) {
                $user->assignRole($role->name);
            }
            $response = [
                'success' => true,
                'message' => __('users.add_user_success'),
            ];
        } else {
            $response = [
                'success' => false,
                'message' => __('users.add_user_error'),
            ];
        }

        if ($response['success']) {
            return redirect(route('admin.users.index'))->with('success', $response['message']);
        } else {
            return back()->with('error', $response['message']);
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
        $user = User::find($id);
        $roles = Role::get();
        return view('admin.users.edit', compact('roles', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|email|max:250|',
            'password' => 'sometimes',
            'role' => 'required',
            'active' => 'required'
        ]);
        $user = User::find($id);
        if (!empty($user)) {
            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'status' => $request->active ? 1 : 0
            ];
            if (!empty($request->password)) {
                $data['password'] = $request->password;
            }
            $user->update($data);
            $role = Role::find($request->role);
            if (!empty($role)) {
                $user->syncRoles($role->name);
            }
            $response = [
                'success' => true,
                'message' => 'User updated successfully!'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Unable to update User!'
            ];
        }
        if ($response['success']) {
            return redirect(route('admin.users.index'))->with('success', $response['message']);
        } else {
            return back()->with('error', $response['message']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->delete()) {
                return back()->with('success', 'User deleted successfully!');
            } else {
                return back()->with('error', 'Unable to delete User!');
            }
        }
    }

    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        // $order = end($data['order']);
        // $orderby = $data['columns'][$order['column']]['data'];
        $iTotalRecords = new User;
        $users = new User;

        if (!empty($search)) {
            $users = $users->search($search);
        }
        $totalRecordswithFilter = clone $users;
        // $users->orderBy($orderby, $order['dir']);
        $users->orderBy('id', 'ASC');

        /*Set limit offset */
        $users = $users->offset(intval($data['start']));
        $users = $users->limit(intval($data['length']));

        $users = $users->get();
        foreach ($users as $k => $val) {

            $users[$k]['profile'] = "<img src='" . asset($val->profile_pic) . "' alt='Logo' width='50px'>";
            $users[$k]['name_link'] = !empty($val->full_name) ? '<a href=' . route('admin.users.edit', $val->id) . '>' . $val->full_name . '</a>' : '-';
            $users[$k]['role'] = $val->getRole();
            $users[$k]['status_span'] = $val->status ? "<span class='badge badge-success'>Active</span>" : "<span class='badge badge-danger'>Inactive</span>";
            $users[$k]['action'] = view('admin.users.action')->with('user', $val)->render();
            $users[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $users,
        ]);
    }
}
