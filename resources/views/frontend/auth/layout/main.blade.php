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
</body>

</html>
