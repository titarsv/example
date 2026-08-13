{{-- Тонкая, навсегда стабильная оболочка — импорт тем её больше не трогает, сам контент живёт в
     теме (public.errors.404_content, resources/themes/{theme}/views/public/errors/404_content.blade.php)
     и переопределяется как любой другой файл темы. См. docs/dynamic-page-import-plan.md,
     «Решено: 404 заводится в тему через тонкую стабильную оболочку». --}}
@extends('public.layouts.main')
@section('title','Error 404')
@section('content')
    @include('public.errors.404_content')
@endsection