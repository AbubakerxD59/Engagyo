{{-- Initial shell while document/CSS/JS load (slow connections). Styles live in schedule assets/style.blade.php --}}
<div class="schedule-page-skeleton-inner container-fluid py-3">
    <div class="schedule-sk-card-shell mb-3">
        <div class="schedule-sk-card-header schedule-sk-base schedule-sk-line-lg mb-3"></div>
        <div class="schedule-sk-account-row mb-3">
            @for ($i = 0; $i < 4; $i++)
                <div class="schedule-sk-account-pill schedule-sk-base"></div>
            @endfor
        </div>
        <div class="schedule-sk-line schedule-sk-base schedule-sk-textarea mb-2"></div>
        <div class="schedule-sk-dropzone schedule-sk-base mb-3"></div>
        <div class="schedule-sk-actions">
            @for ($i = 0; $i < 4; $i++)
                <div class="schedule-sk-btn schedule-sk-base"></div>
            @endfor
        </div>
    </div>
    <div class="schedule-sk-card-shell">
        <div class="schedule-sk-card-header schedule-sk-base schedule-sk-line-lg mb-3"></div>
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="schedule-sk-line schedule-sk-base schedule-sk-label mb-2"></div>
                <div class="schedule-sk-line schedule-sk-base h-38"></div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="schedule-sk-line schedule-sk-base schedule-sk-label mb-2"></div>
                <div class="schedule-sk-line schedule-sk-base h-38"></div>
            </div>
        </div>
        <div class="schedule-posts-grid">
            @for ($i = 0; $i < 6; $i++)
                <div class="schedule-post-skeleton-card">
                    <div class="schedule-post-skeleton-preview schedule-sk-base"></div>
                    <div class="schedule-post-skeleton-meta">
                        <div class="schedule-sk-line schedule-sk-base w-35 mb-2"></div>
                        <div class="schedule-sk-line schedule-sk-base w-85 mb-2"></div>
                        <div class="schedule-sk-line schedule-sk-base w-60 mb-2"></div>
                        <div class="schedule-sk-line schedule-sk-base w-45"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>
