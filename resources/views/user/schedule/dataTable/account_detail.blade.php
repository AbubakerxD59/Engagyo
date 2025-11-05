<div>
    <span>
        <img style="width:35px;height:35px;" src="{{ $post->social_profile }}" class="rounded-circle"
            onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';">
        <img src="{{ $post->social_profile }}" alt="" style="width: 15px; position:relative;"
            onerror="this.onerror=null; this.src='{{ social_logo($post->social_type) }}';">
        <b>{{ $post->account_name ?? ucfirst($post->social_type) }}</b>
    </span>
</div>
