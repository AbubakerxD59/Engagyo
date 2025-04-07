<div class="modal fade get_role_permissions{{ $role_id }}" tabindex="-1" role="dialog"
    aria-labelledby="mySmallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Permissions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('roles.assign_permissions') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="role_id" value="{{ $role_id }}">
                    <div class="d-flex justify-content-around flex-wrap">
                        @foreach ($permission_head as $key => $head)
                            <?php
                            $permissions = \App\Models\Permission::where('parent_id', $head->id)->get();
                            $role = \App\Models\Role::find($role_id);
                            $role_permissions = $role->permissions()->pluck('id')->toArray();
                            ?>
                            <div class="card col-5">
                                <div class="card-body d-flex flex-wrap">
                                    <div class="col-12">
                                        <h5 class="card-title col-10">{{ strtoupper($head->label) }}</h5>
                                        @if ($role->name != 'Super Admin')
                                            <input type="checkbox" class="check-section-permissions"
                                                data-id="{{ $key + 1 }}">
                                        @endif
                                    </div>
                                    <div class="col-12 px-0 pt-3">
                                        @foreach ($permissions as $permission)
                                            <div class="col-12">
                                                <label class="col-10"
                                                    for="permissions_[{{ $role_id }}][{{ $permission->id }}]">{{ $permission->label }}</label>
                                                <input type="checkbox" class="permission-check-{{ $key + 1 }}"
                                                    name="permissions[]" value="{{ $permission->id }}"
                                                    id="permissions_[{{ $role_id }}][{{ $permission->id }}]"
                                                    {{ in_array($permission->id, $role_permissions) ? 'checked' : '' }}
                                                    {{ $role->name == 'Super Admin' ? 'disabled' : '' }}>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- <table class="table table-bordered table-hover">
                      <thead>
                          <tr>
                              <th>View</th>
                              <th>Add</th>
                              <th>Edit</th>
                              <th>Delete</th>
                          </tr>
                      </thead>
                      <tbody>
                          @foreach ($permission_head as $head)
                              <tr>
                                  <td colspan="5" align="center"><strong>{{ strtoupper($head->label) }}</strong></td>
                              </tr>
                              <?php
                              $permissions = \App\Models\Permission::where('parent_id', $head->id)->get();
                              ?>
                              <tr>
                                  @foreach ($permissions as $permission)
                                      <td align="center">
                                          <input type="checkbox"
                                              name="permissions[{{ $permission->id }}]"value="{{ $permission->id }}"
                                              id="permissions[{{ $permission->id }}]">
                                      </td>
                                  @endforeach
                              </tr>
                          @endforeach
                      </tbody>
                  </table> --}}
                </div>
                <div class="modal-footer">
                    @can('assign_role_permissions')
                        <?php
                        $role = \App\Models\Role::find($role_id);
                        ?>
                        @if ($role->name != 'Super Admin')
                            <button type="submit" class="btn btn-outline-primary">Submit</button>
                        @endif
                    @endcan
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
