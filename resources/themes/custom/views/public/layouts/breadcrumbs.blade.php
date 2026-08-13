@if ($breadcrumbs)
    @include('public.layouts.microdata.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            @foreach ($breadcrumbs as $i => $breadcrumb)
                @if(!empty($breadcrumb->url) && $i + 1 < count($breadcrumbs))
                    <li class="breadcrumb-item"><a href="{{ $breadcrumb->url }}" class="text-decoration-none">{{ $breadcrumb->title }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb->title }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
