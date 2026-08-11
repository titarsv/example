<head>
    <meta charset="utf-8">

    @if(!empty($seo))
        <title>{{ !empty($seo->getAttributes()['meta_title']) ? $seo->getAttributes()['meta_title'] : $seo->meta_title }}</title>
        <meta name="description" content="{{ !empty($seo->getAttributes()['meta_description']) ? $seo->getAttributes()['meta_description'] : $seo->meta_description }}">
        <meta name="keywords" content="{{ $seo->meta_keywords or '' }}">

        @if(!empty($seo->canonical))
            <link rel="canonical" href="{{ $seo->canonical }}">
        @endif
        @if(!empty($seo->robots))
            <meta name="robots" content="{{ $seo->robots }}">
        @endif

        @if(!empty($pagination) && $pagination->currentPage() > 1)
            <link rel="prev" href="{{ $cp->url($pagination->url($pagination->currentPage() - 1), $pagination->currentPage() - 1) }}">
        @endif
        @if(!empty($pagination) && $pagination->currentPage() < $pagination->lastPage())
            <link rel="next" href="{{ $cp->url($pagination->url($pagination->currentPage() + 1), $pagination->currentPage() + 1) }}">
        @endif
    @else
        @yield('meta')
    @endif

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $primary_color ?? '#3E3E93' }}">

    <link rel="shortcut icon" href="{{ theme_asset('images/favicon.png') }}" type="image/png">

    <link rel="stylesheet" href="{{ theme_mix('css/app.css') }}">

    @if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false && config('app.debug') === false)
    <!-- Google Tag Manager -->
    {!! $settings->gtm !!}
    <!-- End Google Tag Manager -->
    @endif
    @include('public.layouts.microdata.local_business')
    @yield('page_vars')
</head>
