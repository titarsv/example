<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "{{ $settings->ld_type }}",
    "name": "{{ $settings->ld_name }}",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ $settings->ld_street }}",
        "addressLocality": "{{ $settings->ld_city }}",
        "addressRegion": "{{ $settings->ld_region }}",
        "postalCode": "{{ $settings->ld_postcode }}"
    },
    "image": "{{ empty($logo) ? '' : $logo->url() }}",
    "telePhone": "{{ $settings->ld_phone }}",
    "url": "{{ str_replace(['http://', 'https://'], '', env('APP_URL')) }}",
    "paymentAccepted": [
        @foreach($settings->ld_payments as $i => $payment)
        "{{ $payment }}"{{ $i+1<count($settings->ld_payments) ? ',' : '' }}
        @endforeach
    ],
   "sameAs": [
    @foreach($settings->social as $i => $social)
        @if(!empty($social))
        "{{$social}}"{{ $i+1<count($settings->social) ? ',' : '' }}
        @endif
    @endforeach
   ],
    @php
        $openingHours = [];
        foreach($settings->ld_opening_hours as $day => $hours){
            if(!empty($hours->trigger) && isset($hours->from)){
                $openingHours[$hours->from.'-'.$hours->to][] = $day;
            }
        }
        $i = 1;
    @endphp
    "openingHours": [
       @foreach($openingHours as $time => $days)
        "{{ implode(',', $days) }} {{ $time }}"{{ $i++<count($openingHours) ? ',' : '' }}
    @endforeach
    ],
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": "{{ $settings->ld_latitude }}",
        "longitude": "{{ $settings->ld_longitude }}"
    },
    "priceRange":"$$"
}
</script>
