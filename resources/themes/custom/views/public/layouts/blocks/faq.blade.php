<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    @foreach($fields['questions'] as $i => $questions)
    @if($i > 0),@endif
    {
      "@type": "Question",
      "name": {!! json_encode($questions->question) !!},
      "acceptedAnswer": {
        "@type": "Answer",
        "text": {!! json_encode(strip_tags($questions->answer)) !!}
      }
    }
    @endforeach
  ]
}
</script>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">{{ $fields['questions_title'] }}</h2>
        <a href="{{ $fields['questions_button_link'] }}" class="text-decoration-none">{{ $fields['questions_button_text'] }}</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-5">
            {!! !empty($fields['questions_image']['image']) ? $fields['questions_image']['image']->image([658, 498], ['alt' => env('APP_NAME'), 'loading' => 'lazy', 'class' => 'img-fluid rounded'], 'cover', ['100vw']) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded" loading="lazy">' !!}
        </div>
        <div class="col-lg-7">
            <div class="accordion" id="faqBlockAccordion">
                @foreach($fields['questions'] as $i => $questions)
                    <div class="accordion-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                        <h3 class="accordion-header">
                            <button class="accordion-button{{ $i ? ' collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faqBlock{{ $i }}" itemprop="name">
                                {{ $questions->question }}
                            </button>
                        </h3>
                        <div id="faqBlock{{ $i }}" class="accordion-collapse collapse{{ $i ? '' : ' show' }}" data-bs-parent="#faqBlockAccordion" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                            <div class="accordion-body" itemprop="text">{!! $questions->answer !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
