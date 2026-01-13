@extends('user.layout.main')
@section('title', 'Team Members')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <div class="float-left">
                <h1 class="m-0">Team Members</h1>
            </div>
            <div class="float-right">
                <a href="{{ route('panel.team-members.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Invite Team Member
                </a>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Invited At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($teamMembers as $member)
                                            <tr>
                                                <td>{{ $member->email }}</td>
                                                <td>{{ $member->member ? $member->member->full_name : 'Pending' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $member->status === 'active' ? 'success' : ($member->status === 'pending' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($member->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $member->invited_at->format('Y-m-d H:i') }}</td>
                                                <td>
                                                    <a href="{{ route('panel.team-members.edit', $member->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form action="{{ route('panel.team-members.destroy', $member->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No team members found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

