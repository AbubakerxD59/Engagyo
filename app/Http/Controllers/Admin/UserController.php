<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();
        return view('admin.users.add', compact('roles', 'packages'));
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
            'package_id' => 'nullable|exists:packages,id',
            'full_access' => 'nullable|boolean',
        ]);

        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
        ];

        if ($request->has('package_id') && $request->package_id) {
            $userData['package_id'] = $request->package_id;
        }

        $user = User::create($userData);

        if (!empty($user)) {
            // Assign role
            $role = Role::find($request->role);
            if (!empty($role)) {
                $user->assignRole($role->name);
            }

            // Assign package if selected
            if ($request->has('package_id') && $request->package_id) {
                $package = Package::find($request->package_id);

                if ($package) {
                    // Deactivate any existing active packages
                    UserPackage::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    // Calculate expiry date
                    $expiresAt = null;
                    if (!$request->has('full_access') || !$request->full_access) {
                        // Calculate expiry based on package duration
                        $duration = $package->duration;
                        $dateType = $package->date_type;

                        if ($dateType == 'day') {
                            $expiresAt = now()->addDays($duration);
                        } elseif ($dateType == 'month') {
                            $expiresAt = now()->addMonths($duration);
                        } elseif ($dateType == 'year') {
                            $expiresAt = now()->addYears($duration);
                        }
                    }

                    // Create user package record
                    UserPackage::create([
                        'user_id' => $user->id,
                        'package_id' => $package->id,
                        'is_active' => true,
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'expires_at' => $expiresAt,
                    ]);
                }
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
        $user = User::with([
            'package',
            'userPackages.package.features',
            'facebook',
            'pinterest',
            'tiktok',
            'pages',
            'boards',
            'domains',
            'posts',
            'apiKeys',
        ])->find($id);
        $roles = Role::get();
        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();
        $featuresWithUsage = $user->getFeaturesWithUsage();
        dd($featuresWithUsage);
        return view('admin.users.edit', compact('roles', 'user', 'packages', 'featuresWithUsage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|email|max:250|unique:users,email,' . $id,
            'role' => 'required',
            'active' => 'required',
            'package_id' => 'nullable|exists:packages,id',
            'full_access' => 'nullable|boolean',
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

            if ($request->has('package_id') && $request->package_id) {
                $data['package_id'] = $request->package_id;
            } elseif ($request->has('package_id') && empty($request->package_id)) {
                $data['package_id'] = null;
            }

            $user->update($data);

            // Assign role
            $role = Role::find($request->role);
            if (!empty($role)) {
                $user->syncRoles($role->name);
            }

            // Handle package assignment
            if ($request->has('package_id') && $request->package_id) {
                $package = Package::find($request->package_id);

                if ($package) {
                    // Deactivate any existing active packages
                    UserPackage::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    // Calculate expiry date
                    $expiresAt = null;
                    if (!$request->has('full_access') || !$request->full_access) {
                        // Calculate expiry based on package duration
                        $duration = $package->duration;
                        $dateType = $package->date_type;

                        if ($dateType == 'day') {
                            $expiresAt = now()->addDays($duration);
                        } elseif ($dateType == 'month') {
                            $expiresAt = now()->addMonths($duration);
                        } elseif ($dateType == 'year') {
                            $expiresAt = now()->addYears($duration);
                        }
                    }

                    // Create user package record
                    UserPackage::create([
                        'user_id' => $user->id,
                        'package_id' => $package->id,
                        'is_active' => true,
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'expires_at' => $expiresAt,
                    ]);
                }
            } elseif ($request->has('package_id') && empty($request->package_id)) {
                // If package_id is empty, deactivate all user packages
                UserPackage::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
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
        $order = end($data['order']);
        $orderby = $data['columns'][$order['column']]['data'] ?? 'id';

        // Map column names to actual database columns
        $columnMapping = [
            'id' => 'id',
            'name_link' => 'first_name',
            'email' => 'email',
            'package_html' => 'package_id',
            'role_name' => 'id', // Will sort by id as fallback
            'status_span' => 'status',
        ];

        $orderColumn = $columnMapping[$orderby] ?? 'id';

        $iTotalRecords = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Super Admin');
        });
        $users = User::with(['package'])->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Super Admin');
        });

        if (!empty($search)) {
            $users = $users->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $totalRecordswithFilter = clone $users;
        $users->orderBy($orderColumn, $order['dir']);

        /*Set limit offset */
        $users = $users->offset(intval($data['start']));
        $users = $users->limit(intval($data['length']));

        $users = $users->get();
        $users->append(['name_link', 'package_html', 'role_name', 'status_span', 'action']);

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $users,
        ]);
    }
}
