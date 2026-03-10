@php
    $pages = $user->pages()->with('facebook')->orderBy('name')->get();
    $boards = $user->boards()->with('pinterest')->orderBy('name')->get();
    $tiktoks = $user->tiktok()->orderBy('username')->get();
    $total = $pages->count() + $boards->count() + $tiktoks->count();
    $limit = null;
    $remaining = null;
    if ($user->activeUserPackage && $user->activeUserPackage->package) {
        $socialAccountsFeature = $user->activeUserPackage->package->features()
            ->where('key', 'social_accounts')
            ->wherePivot('is_enabled', true)
            ->first();
        if ($socialAccountsFeature) {
            $limit = $socialAccountsFeature->pivot->limit_value ?? null;
            if ($limit !== null && $limit > 0) {
                $remaining = max(0, $limit - $total);
            }
        }
    }
@endphp
<div class="mb-3">
    <div class="alert alert-info mb-0">
        <strong>Total:</strong> {{ $total }} connected account(s)
        @if ($limit !== null && $limit > 0)
            | <strong>Limit:</strong> {{ $limit }} | <strong>Remaining:</strong> {{ $remaining }}
        @endif
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white py-2">
                <i class="fab fa-facebook-f mr-1"></i> Facebook Pages
                <span class="badge badge-light float-right">{{ $pages->count() }}</span>
            </div>
            <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                @forelse ($pages as $page)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <img src="{{ $page->profile_image }}" class="rounded-circle mr-2" width="32" height="32"
                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                        <div class="text-truncate" title="{{ $page->name }}">
                            {{ $page->name }}
                            @if ($page->facebook)
                                <small class="text-muted d-block">{{ '@' . $page->facebook->username }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No Facebook pages connected</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-danger text-white py-2">
                <i class="fab fa-pinterest-p mr-1"></i> Pinterest Boards
                <span class="badge badge-light float-right">{{ $boards->count() }}</span>
            </div>
            <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                @forelse ($boards as $board)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <img src="{{ ($board->pinterest && $board->pinterest->profile_image) ? $board->pinterest->profile_image : social_logo('pinterest') }}" class="rounded-circle mr-2" width="32" height="32"
                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                        <div class="text-truncate" title="{{ $board->name }}">
                            {{ $board->name }}
                            @if ($board->pinterest)
                                <small class="text-muted d-block">{{ '@' . $board->pinterest->username }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No Pinterest boards connected</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white py-2">
                <i class="fab fa-tiktok mr-1"></i> TikTok Accounts
                <span class="badge badge-light float-right">{{ $tiktoks->count() }}</span>
            </div>
            <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                @forelse ($tiktoks as $tiktok)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <img src="{{ $tiktok->profile_image }}" class="rounded-circle mr-2" width="32" height="32"
                            onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';">
                        <div class="text-truncate" title="{{ $tiktok->display_name ?? $tiktok->username }}">
                            {{ $tiktok->display_name ?? $tiktok->username }}
                            <small class="text-muted d-block">{{ '@' . $tiktok->username }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No TikTok accounts connected</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
