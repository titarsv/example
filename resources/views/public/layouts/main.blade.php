<!DOCTYPE html>
<html lang="{{ App::getLocale() == 'ua' ? 'uk' : App::getLocale() }}" prefix="og: http://ogp.me/ns#">
@include('public.layouts.head')
<body class="{{ Request::path()=='/' ? 'home' : '' }}">
@if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false)
<!-- Google Tag Manager (noscript) -->
{!! $settings->gtm_noscript !!}
<!-- End Google Tag Manager (noscript) -->
@endif
@include('public.layouts.header', ['root_category' => isset($root_category) ? $root_category : false])
<main id="main">
    @yield('content')
</main>
@include('public.layouts.footer')
@include('public.layouts.footer-scripts')
</body>
</html>
