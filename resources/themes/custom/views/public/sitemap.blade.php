@extends('public.layouts.main')
@section('meta')
    <title>{{ $title }}</title>
    <meta name="robots" content="noindex, follow" />
@endsection
@section('page_vars')
    @if(!isset($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false)
{{--        <!-- Facebook Pixel Code -->--}}
{{--        <script>--}}
{{--            !function(f,b,e,v,n,t,s)--}}
{{--            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?--}}
{{--                n.callMethod.apply(n,arguments):n.queue.push(arguments)};--}}
{{--                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';--}}
{{--                n.queue=[];t=b.createElement(e);t.async=!0;--}}
{{--                t.src=v;s=b.getElementsByTagName(e)[0];--}}
{{--                s.parentNode.insertBefore(t,s)}(window, document,'script',--}}
{{--                'https://connect.facebook.net/en_US/fbevents.js');--}}
{{--            fbq('init', '1172440259605977');--}}
{{--            fbq('track', 'PageView');--}}
{{--        </script>--}}
{{--        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1172440259605977&ev=PageView&noscript=1"/></noscript>--}}
{{--        <!-- End Facebook Pixel Code -->--}}
    @endif
    @include('public.layouts.microdata.open_graph', [
     'title' => $title,
     'description' => $title,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection
@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">{{ $title }}</h1>
        <div class="row g-2">
            @foreach($links as $link => $name)
                <a href="{{ $link }}" class="col-md-3 col-sm-6">{{ $name }}</a>
            @endforeach
        </div>
        @if(isset($pagination))
            <div class="row">
                @include('public.layouts.pagination', ['paginator' => $pagination])
            </div>
        @endif
    </div>
@endsection
