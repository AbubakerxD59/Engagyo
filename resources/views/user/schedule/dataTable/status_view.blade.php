<div>
    @if ($post->status == '1')
        <span class="badge badge-success">Published</span>
    @elseif ($post->status == '0')
        <span class="badge badge-primary">Pending</span>
    @elseif ($post->status == '-1')
        <span class="badge badge-danger">Failed</span>
    @endif
    @if ($post->published_at)
        <br>
        <span class="badge badge-info">Published At: {{ date('Y-m-d h:i A', strtotime($post->published_at)) }}</span>
    @endif
</div>
