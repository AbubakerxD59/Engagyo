@if (Session::has('error'))
    <div class="alert alert-info">{{ Session::get('error') }}</div>
@endif
