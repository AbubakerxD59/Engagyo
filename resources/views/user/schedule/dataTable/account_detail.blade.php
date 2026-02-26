<div>
    <span>
        {{-- account profile --}}
        <img style="width:35px;height:35px;" src="{{ $post->account_profile }}" class="rounded-circle" loading="lazy"
            onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';">
        {{-- social logo --}}
        <img src="{{ social_logo($post->social_type) }}" alt="" style="width: 15px; position:relative;" loading="lazy"
            onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';">
        {{-- account name --}}
        <b>{{ $post->account_name ?? ucfirst($post->social_type) }}</b>
    </span>
</div>
