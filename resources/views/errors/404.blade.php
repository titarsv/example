@extends('public.layouts.main')

{{-- page title --}}
@section('title','Error 404')

@section('content')
    <div class="section page-nf">
        <div class="container">
            <nav class="breadcrumbs">
                <a href="/">Home</a>
                <span>Not found</span>
            </nav>
            <h1 class="page-title">Oops! Looks like you've wandered off the map.</h1>
            <img class="nf-pic" src="/images/404.png" loading="lazy" alt="404">
            <div class="nf-text">Even we’re not sure where this page went — probably chilling somewhere else.</div>
            <a href="/" class="btn">Back to Homepage</a>
        </div>
    </div>
@endsection
