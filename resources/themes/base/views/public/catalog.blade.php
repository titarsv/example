@extends('public.layouts.main', ['pagination' => $products, 'root_category' => !empty($category) ? $category->get_root_category() : null])
@section('page_vars')
    @if(!empty($category))
        @include('public.layouts.microdata.category', ['category' => $category])
    @endif
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">
            @if(!empty($additional_crumb))
                {!! Breadcrumbs::render('filter', $category, $additional_crumb) !!}
            @elseif(!empty($category))
                {!! Breadcrumbs::render('categories', $category) !!}
            @else
                {!! Breadcrumbs::render('catalog') !!}
            @endif
        </div>
        <h1 class="h3 mb-4">{{ !empty($seo->getAttributes()['name']) ? $seo->getAttributes()['name'] : $seo->name }}</h1>

        @include('public.layouts.catalog_filterable_area')

        @if(!empty($seo->description))
            <div class="text-muted mt-4">{!! $seo->description !!}</div>
        @endif
    </div>
@endsection
