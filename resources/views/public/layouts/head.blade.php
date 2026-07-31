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

    <link rel="preload" href="/fonts/Poppins-Regular.woff2?709727e405db435e05b1ffac105046bd" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/Poppins-Medium.woff2?967ee284888df9dbc3c7c6dbb6436008" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/Unbounded-Regular.woff2?5e67c3bf07ee219be03f7bc378af059a" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/Unbounded-Medium.woff2?84adb91f28fe5fe360a300f70476623d" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/Unbounded-SemiBold.woff2?c1063e690708e29b247994aadb925b2f" as="font" type="font/woff2" crossorigin>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fff">

    <!-- Template Basic Images Start -->
    <link rel="shortcut icon" href="/images/favicon.png" type="image/png">
    <!-- Template Basic Images End -->

    @if(!empty($critical_css))
        @if(is_file(public_path($critical_css)))
            <style>{!! file_get_contents(public_path($critical_css)) !!}</style>
        @else
            <link rel="stylesheet" href="{{ mix($critical_css) }}">
        @endif
    @else
        @if(is_file(public_path('css/header.css')))
            <style>{!! file_get_contents(public_path('css/header.css')) !!}</style>
        @else
            <link rel="stylesheet" href="{{ mix("css/header.css") }}">
        @endif
    @endif

    <script>!function(e){"use strict";function t(e,t,n){e.addEventListener?e.addEventListener(t,n,!1):e.attachEvent&&e.attachEvent("on"+t,n)};function n(t,n){return e.localStorage&&localStorage[t+"_content"]&&localStorage[t+"_file"]===n};function a(t,a){if(e.localStorage&&e.XMLHttpRequest)n(t,a)?o(localStorage[t+"_content"]):l(t,a);else{var s=r.createElement("link");s.href=a,s.id=t,s.rel="stylesheet",s.type="text/css",r.getElementsByTagName("head")[0].appendChild(s),r.cookie=t}}function l(e,t){var n=new XMLHttpRequest;n.open("GET",t,!0),n.onreadystatechange=function(){4===n.readyState&&200===n.status&&(o(n.responseText),localStorage[e+"_content"]=n.responseText,localStorage[e+"_file"]=t)},n.send()}function o(e){var t=r.createElement("style");t.setAttribute("type","text/css"),r.getElementsByTagName("head")[0].appendChild(t),t.styleSheet?t.styleSheet.cssText=e:t.innerHTML=e}var r=e.document;e.loadCSS=function(e,t,n){var a,l=r.createElement("link");if(t)a=t;else{var o;o=r.querySelectorAll?r.querySelectorAll("style,link[rel=stylesheet],script"):(r.body||r.getElementsByTagName("head")[0]).childNodes,a=o[o.length-1]}var s=r.styleSheets;l.rel="stylesheet",l.href=e,l.media="only x",a.parentNode.insertBefore(l,t?a:a.nextSibling);var c=function(e){for(var t=l.href,n=s.length;n--;)if(s[n].href===t)return e();setTimeout(function(){c(e)})};return l.onloadcssdefined=c,c(function(){l.media=n||"all"}),l},e.loadLocalStorageCSS=function(l,o){n(l,o)||r.cookie.indexOf(l)>-1?a(l,o):t(e,"load",function(){a(l,o)})}}(this);</script>

    @if(empty($css))
        <noscript>
            <link rel="stylesheet" href="{{ mix("css/app.css") }}">
        </noscript>
        <script>loadCSS( "{{ mix("css/app.css") }}", false, "all" );</script>
    @else
        <noscript>
            <link rel="stylesheet" href="{{ mix($css) }}">
        </noscript>
        <script>loadCSS( "{{ mix($css) }}", false, "all" );</script>
    @endif

    <script>
        performance.mark("stylesheets done blocking");
    </script>
    @if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false && config('app.debug') === false)
    <!-- Google Tag Manager -->
    {!! $settings->gtm !!}
    <!-- End Google Tag Manager -->
    @endif
    @include('public.layouts.microdata.local_business')
    @yield('page_vars')
</head>
