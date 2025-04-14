<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google-site-verification" content="IDiRooS27YqsymcxNBE2wDm398kjRwzjIQ1iXvzMOPQ"  />
    @yield('blog_seo')
    <meta name="author" content="admin">
    <!-- SEO META TAGS -->
    <title>{{ env('APP_NAME', 'Engagyo') }}</title>
    <meta name="facebook-domain-verification" content="6mnak5vj94u9re6sam9banbb85hu64" />
    <meta name="tiktok-developers-site-verification" content="p1EA7Z2Ju0dgJ4c3KYB1xes9XqIP0I60" />
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap"
        rel="stylesheet">

    <!-- Sweet Alerts -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.css') }}">
    {{-- Toastr --}}
    <link href="{{ asset('assets/plugins/toastr/toastr.css') }}" rel="stylesheet">
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/bulstyle.css') }}">
    @stack('styles')
</head>

<body>
    @include('frontend.layout.navbar')
    @yield('body')
    @include('frontend.layout.faq')
    @include('frontend.layout.newsletter')
    @include('frontend.layout.about_us')
    @include('frontend.layout.footer')
</body>

</html>
