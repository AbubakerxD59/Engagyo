<div class="pinterest_card">
    <!-- IMAGE/MEDIA SECTION -->
    <div class="image-container">
        @if ($post->type == 'video')
            <!-- Video Thumbnail Placeholder -->
            <div class="video-thumbnail-placeholder">
                <div class="video-placeholder-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="video-play-overlay">
                    <div class="video-play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        @else
            <!-- Regular Image -->
            <img src="{{ $post->image }}" alt="Product post image" class="post-image"
                onerror="this.onerror=null; this.src='{{ asset('assets/img/downloading_sample.png') }}';">
        @endif
    </div>
    <!-- CONTENT SECTION -->
    <div class="card-content">
        <div class="mb-2">
            <img src="{{ $post->account_profile }}" class="rounded-circle me-2 social_profile"
                style="object-fit: cover;"
                onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';">
            <span>
                <strong>
                    {{ $post->account_name ?? ucfirst($post->social_type) }}
                </strong>
            </span>
        </div>
        <span>
            @if ($post->type == 'link')
                <a href="{{ $post->url }}" target="_blank">{{ $post->title }}</a>
            @else
                {{ $post->title }}
            @endif
        </span>
        <!-- COMMENTS SECTION -->
        <div class="comment-input-container">
            <p class="mb-3 text-muted">No comments yet</p>
            <div class="d-flex align-items-center">
                <input type="text" class="form-control comment-input"
                    placeholder="Add a comment to start the conversation">
                <div class="d-flex ms-3">
                    <i class="bi bi-emoji-smile comment-icon me-3"></i>
                    <i class="bi bi-chat-square-text comment-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>
