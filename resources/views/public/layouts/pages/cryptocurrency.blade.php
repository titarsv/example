@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="page-crypto">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
                <div class="page-description">{!! $fields['description'] !!}</div>
            </div>
        </div>
        <div class="crypto-wrapper">
            <div class="container">
                @foreach($fields['steps'] as $i => $steps)
                    <div class="crypto-item">
                        <div class="crypto-item__head">
                            <span>{{ $i + 1 }}</span>
                            <div>{{ $steps->title }}</div>
                        </div>
                        <div class="crypto-item__body">
                            @if(isset($steps->subsection))
                                @foreach($steps->subsection as $subsection)
                                    {!! $subsection->text !!}
                                    @if(!empty($subsection->images) && !empty($subsection->images[0]->image['image']))
                                        <div class="crypto-item__screens">
                                            @foreach($subsection->images as $images)
                                                @if(!empty($images->image['image']))
                                                    <div class="screen">
                                                        {!! $images->image['image']->image([170, 300], ['alt' => $images->image['image']->alt, 'loading' => 'lazy'], 'cover', ['170px']) !!}
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
