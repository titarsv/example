{{-- Тонкая, навсегда стабильная оболочка — импорт тем её больше не трогает, сам контент живёт в
     теме (public.errors.404_content, resources/themes/{theme}/views/public/errors/404_content.blade.php)
     и переопределяется как любой другой файл темы. См. docs/dynamic-page-import-plan.md,
     «Решено: 404 заводится в тему через тонкую стабильную оболочку». --}}
@extends('public.layouts.main')
@section('title','Error 404')
@section('page_vars')
    {{-- theme_mix_if_exists(), не theme_mix() — у базовой темы (и у тем без своего донора-404)
         своего изолированного css/imported/404.css нет вовсе, голый theme_mix() бросил бы
         исключение на каждой странице 404, а не только там, где такой бандл реально есть. --}}
    @if($__pageCss = theme_mix_if_exists('css/imported/404.css'))
        <link rel="stylesheet" href="{{ $__pageCss }}">
    @endif
@endsection
@section('content')
    @include('public.errors.404_content')
@endsection