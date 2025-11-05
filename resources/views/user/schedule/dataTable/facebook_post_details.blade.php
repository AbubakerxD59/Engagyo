<div class="facebook_card social-post-card">
    <!-- 1. POST HEADER -->
    <div class="card-header bg-white border-0 p-3 d-flex justify-content-between align-items-start">
        <div class="d-flex">
            <!-- Profile Picture -->
            <img src="{{ $post->account_profile }}" class="rounded-circle me-2" style="object-fit: cover;"
                onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';" width="15%">
            <div class="post-header-text ml-2">
                <div class="post-author">
                    {{ $post->account_name ?? ucfirst($post->social_type) }}
                </div>
                <div class="post-date">{{ date('F j, Y', strtotime($post->publish_date)) }} <i
                        class="fas fa-globe-americas"></i></div>
            </div>
        </div>
    </div>
    <div class="mb-3 px-3">
        <span>
            {{ $post->title }}
        </span>
        @if (!empty($post->url))
            <hr class="m-2">
            <a href="{{ $post->url }}" target="_blank">{{ $post->url }}</a>
        @endif
    </div>
    <div class="card-body p-0">
        @if ($post->type == 'photo')
            <div class="col-12 content-block">
                <div class="content-block-inner text-center">
                    <div class="pronunciation-image-container">
                        <!-- Placeholder for Pizza Image -->
                        <img src="{{ $post->image }}" alt="Pizza"
                            onerror="this.onerror=null; this.src='{{ no_image() }}';">
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 3. POST FOOTER (ACTIONS) -->
    <div class="card-footer bg-white border-0 pb-2">
        <!-- Action Buttons (Like, Comment, Share) -->
        <div class="post-actions d-flex text-center">
            <div class="action-btn flex-fill p-2"><i class="far fa-thumbs-up me-1"></i> Like</div>
            <div class="action-btn flex-fill p-2"><i class="far fa-comment-alt me-1"></i> Comment</div>
            <div class="action-btn flex-fill p-2"><i class="fas fa-share me-1"></i> Share</div>
        </div>
        <!-- Comment Box -->
        <div class="d-flex align-items-center mt-2">
            <img src="{{ $post->account_profile }}" class="rounded-circle me-2" alt="Comment Profile"
                style="object-fit: cover;"
                onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';" width="8%">
            <div class="flex-grow-1 p-2 rounded-pill bg-light text-muted" style="font-size: 12px;">
                @if (!empty($post->comment))
                    {{ $post->comment }}
                @else
                    Comment as Muhammad Abubaker...
                @endif
            </div>
        </div>
    </div>
</div>
