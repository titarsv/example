@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-faq">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
        <div class="faq-main">
            <div class="container">
                <ul class="faq-tabs">
                    <li class="all active" data-tab="All Questions">All Questions</li>
                    @foreach($fields['tabs'] as $tabs)
                        <li data-tab="{{ $tabs->name }}">{{ $tabs->name }}</li>
                    @endforeach
                </ul>
                <div class="faq-list" itemscope="" itemtype="https://schema.org/FAQPage">
                    @foreach($fields['questions'] as $i => $questions)
                        <div class="accordion-item{{ $i ? '' : ' active' }}" data-content="{{ $questions->tab }}" itemscope="" itemprop="mainEntity" itemtype="https://schema.org/Question">
                            <div class="accordion-item__head">
                                <div itemprop="name">{{ $questions->question }}</div>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                      <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                      <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                      <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="accordion-item__body"{!! $i ? '' : ' style="display: block;"' !!} itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div itemprop="text">
                                    {!! $questions->answer !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
