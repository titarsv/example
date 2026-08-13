@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('page', $page) !!}</div>
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" class="btn btn-sm btn-primary js-faq-tab" data-tab="all">Все вопросы</button>
            @foreach($fields['tabs'] as $tabs)
                <button type="button" class="btn btn-sm btn-outline-secondary js-faq-tab" data-tab="{{ $tabs->name }}">{{ $tabs->name }}</button>
            @endforeach
        </div>

        <div class="accordion" id="faqAccordion" itemscope itemtype="https://schema.org/FAQPage">
            @foreach($fields['questions'] as $i => $questions)
                <div class="accordion-item js-faq-item" data-tab="{{ $questions->tab }}" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h2 class="accordion-header">
                        <button class="accordion-button{{ $i ? ' collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" itemprop="name">
                            {{ $questions->question }}
                        </button>
                    </h2>
                    <div id="faq{{ $i }}" class="accordion-collapse collapse{{ $i ? '' : ' show' }}" data-bs-parent="#faqAccordion" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <div class="accordion-body" itemprop="text">
                            {!! $questions->answer !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
