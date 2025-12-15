@php
    $packageInfo = $user->getPackageStatusInfo();
    $expiresAt = $packageInfo['expires_at'] ?? null;
@endphp

@if ($packageInfo['package_name'])
    <div>
        <strong>{{ $packageInfo['package_name'] }}</strong>
        <span class="badge {{ $packageInfo['badge_class'] }}">{{ $packageInfo['badge_text'] }}</span>
        @if ($packageInfo['status'])
            <small>
                @if ($packageInfo['expires_at'])
                    <br>
                    {{ $packageInfo['expires_at']->format('jS M, Y') }}
                @endif
            </small>
        @endif
    </div>
@else
    -
@endif
