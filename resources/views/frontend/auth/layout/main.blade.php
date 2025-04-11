
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME', 'Engagyo') }} | @yield('title')</title>
    <link rel="icon" type="image/x-icon" href="{{ site_logo() }}">
    @include('frontend.auth.layout.header')
</head>

<body>
    @yield('authBody')
    @include('frontend.auth.layout.footer')
    @if (Session::has('success'))
        <script type="text/javascript">
            toastr.success('{{ Session::get('success') }}');
        </script>
    @endif
    @if (Session::has('warning'))
        <script type="text/javascript">
            toastr.warning('{{ Session::get('warning') }}');
        </script>
    @endif
    @if (Session::has('error'))
        <script type="text/javascript">
            toastr.error('{{ Session::get('error') }}');
        </script>
    @endif
</body>

</html>
