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

<div class="section section-faq">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">{{ $fields['questions_title'] }}</h2>
            <a class="link-all" href="{{ $fields['questions_button_link'] }}">{{ $fields['questions_button_text'] }}</a>
        </div>
    </div>
    <div class="faq-wrapper">
        <div class="faq-left">
            <span>FAQ’s</span>
            {!! !empty($fields['questions_image']['image']) ? $fields['questions_image']['image']->image([658, 498], ['alt' => env('APP_NAME'), 'loading' => 'lazy', 'width' => 658, 'height' => 498], 'cover', ['100vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
        </div>
        <div class="faq-right faq-list">
            @foreach($fields['questions'] as $i => $questions)
                <div class="accordion-item{{ $i ? '' : ' active' }}" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
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
                    <div class="accordion-item__body"{!! $i ? '' : ' style="display: block;"' !!} itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <div itemprop="text">{!! $questions->answer !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
