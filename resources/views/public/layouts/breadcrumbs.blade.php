@if ($breadcrumbs)
    @include('public.layouts.microdata.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
    <nav class="breadcrumbs">
        @foreach ($breadcrumbs as $i => $breadcrumb)
            @if(!empty($breadcrumb->url) && $i + 1 < count($breadcrumbs))
                <a href="{{ $breadcrumb->url }}">{{ $breadcrumb->title }}</a>
            @else
                <span>{{ $breadcrumb->title }}</span>
            @endif
        @endforeach
    </nav>
@endif
