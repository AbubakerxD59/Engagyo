@php $featuresWithUsage = $user->getFeaturesWithUsage(); @endphp
@if ($featuresWithUsage && $featuresWithUsage->count() > 0)
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Description</th>
                    <th>Limit</th>
                    <th>Used</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($featuresWithUsage as $feature)
                    <tr>
                        <td><strong>{{ $feature['name'] }}</strong></td>
                        <td>{{ $feature['description'] ?? '-' }}</td>
                        <td>
                            @if ($feature['is_unlimited'])
                                <span class="badge badge-info">Unlimited</span>
                            @elseif($feature['is_boolean'])
                                <span
                                    class="badge badge-{{ $feature['usage'] ? 'success' : 'secondary' }}">
                                    {{ $feature['usage'] ? 'Enabled' : 'Disabled' }}
                                </span>
                            @else
                                {{ $feature['limit'] ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($feature['is_boolean'])
                                <span
                                    class="badge badge-{{ $feature['usage'] ? 'success' : 'secondary' }}">
                                    {{ $feature['usage'] ? 'Yes' : 'No' }}
                                </span>
                            @else
                                <strong>{{ $feature['usage'] }}</strong>
                                @if (!$feature['is_unlimited'] && $feature['limit'])
                                    / {{ $feature['limit'] }}
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($feature['is_unlimited'] || $feature['is_boolean'])
                                <span class="badge badge-success">Active</span>
                            @elseif($feature['is_over_limit'])
                                <span class="badge badge-danger">Over Limit</span>
                            @elseif($feature['usage_percentage'] >= 80)
                                <span class="badge badge-warning">Near Limit</span>
                            @else
                                <span class="badge badge-success">Active</span>
                            @endif
                            @if (!$feature['is_unlimited'] && !$feature['is_boolean'] && $feature['limit'])
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar 
                                    {{ $feature['is_over_limit'] ? 'bg-danger' : ($feature['usage_percentage'] >= 80 ? 'bg-warning' : 'bg-success') }}"
                                        role="progressbar"
                                        style="width: {{ min($feature['usage_percentage'], 100) }}%">
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        No features are currently assigned to this user's package.
    </div>
@endif

