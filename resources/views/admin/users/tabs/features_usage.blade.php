@php
    $getAvailableFeaturesArray = $user->getAvailableFeaturesArray();
@endphp
@if (count($getAvailableFeaturesArray) > 0)
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
                @foreach ($getAvailableFeaturesArray as $feature)
                    @php
                        $isBoolean = $feature['type'] === 'boolean';
                        $isUnlimited = $feature['is_unlimited'] ?? false;
                        $limitValue = $feature['limit_value'] ?? null;
                        $usageCount = $feature['usage_count'] ?? 0;

                        // Calculate usage percentage and over limit status for numeric features
                        $usagePercentage = 0;
                        $isOverLimit = false;

                        if (!$isBoolean && !$isUnlimited && $limitValue && $limitValue > 0) {
                            $usagePercentage = round(($usageCount / $limitValue) * 100, 2);
                            $isOverLimit = $usageCount > $limitValue;
                        }
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $feature['name'] }}</strong>
                            @if (!empty($feature['key']))
                                <br><small class="text-muted">{{ $feature['key'] }}</small>
                            @endif
                        </td>
                        <td>{{ $feature['description'] ?: '-' }}</td>
                        <td>
                            @if ($isUnlimited)
                                <span class="badge badge-info">Unlimited</span>
                            @elseif ($isBoolean)
                                <span class="badge badge-{{ $usageCount ? 'success' : 'secondary' }}">
                                    {{ $usageCount ? 'Enabled' : 'Disabled' }}
                                </span>
                            @else
                                {{ $limitValue ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isBoolean)
                                <span class="badge badge-{{ $usageCount ? 'success' : 'secondary' }}">
                                    {{ $usageCount ? 'Yes' : 'No' }}
                                </span>
                            @else
                                <strong>{{ $usageCount }}</strong>
                                @if (!$isUnlimited && $limitValue)
                                    / {{ $limitValue }}
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($isUnlimited || $isBoolean)
                                <span class="badge badge-success">Active</span>
                            @elseif ($isOverLimit)
                                <span class="badge badge-danger">Over Limit</span>
                            @elseif ($usagePercentage >= 80)
                                <span class="badge badge-warning">Near Limit</span>
                            @else
                                <span class="badge badge-success">Active</span>
                            @endif

                            @if (!$isUnlimited && !$isBoolean && $limitValue && $limitValue > 0)
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar 
                                        {{ $isOverLimit ? 'bg-danger' : ($usagePercentage >= 80 ? 'bg-warning' : 'bg-success') }}"
                                        role="progressbar" style="width: {{ min($usagePercentage, 100) }}%"
                                        aria-valuenow="{{ $usagePercentage }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">{{ $usagePercentage }}%</small>
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
