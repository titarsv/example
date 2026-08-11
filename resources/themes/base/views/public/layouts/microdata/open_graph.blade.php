@if(App::getLocale() == 'en')
    <meta property="og:locale" content="en-US" />
@elseif(App::getLocale() == 'ua')
    <meta property="og:locale" content="uk-UA" />
@elseif(App::getLocale() == 'ru')
    <meta property="og:locale" content="ru-UA" />
@endif
@if(!empty($title))
    <meta property="og:title" content="{{ $title }}" />
@endif
@if(!empty($description))
    <meta property="og:description" content="{{ $description }}" />
@endif
<meta property="og:site_name" content="Properloud" />
<meta property="og:type" content="website" />
@if(env('APP_URL') != request()->url())
    <meta property="og:url" content="{{env('APP_URL')}}/{{ Request::path() }}">
@else
    <meta property="og:url" content="{{env('APP_URL')}}">
@endif
@if(!empty($image))
    <meta property="og:image" content="{{env('APP_URL')}}{{ $image }}" />
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $image }}">
@endif
@if(!empty($title))
    <meta name="twitter:title" content="{{ $title }}">
@endif
@if(!empty($description))
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if(env('APP_URL') != request()->url())
    <meta name="twitter:domain" content="{{env('APP_URL')}}/{{ Request::path() }}">
@else
    <meta name="twitter:domain" content="{{env('APP_URL')}}">
@endif
<meta name="twitter:site" content="{{env('APP_URL')}}">
