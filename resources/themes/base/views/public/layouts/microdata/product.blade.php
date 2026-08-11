<script type='application/ld+json'>
{
  "@context": "http://www.schema.org",
  "@type": "{{ !empty($variations) ? 'ProductGroup' : 'Product' }}",
  @if(!empty($brand = $product->get_attribute('brand')))
  "brand": "{{ $brand }}",
  @endif
  "name": "{{ $product->name }}",
  "sku": "{{ $product->sku }}",
  @if(!empty($category = $product->main_category()))
  "category": "{{ $category->name }}",
  @endif
  @if(!empty($product->image))
  "image": "{{ $product->image->url() }}",
  @endif
  "description": "{{ empty($product->seo->meta_description) ? $product->name : str_replace(PHP_EOL, '\n', strip_tags($product->seo->meta_description)) }}",
  "offers": {
    "@type": "Offer",
    "priceCurrency": "GBP",
    "price": "{{ $product->price }}",
    "priceValidUntil": "{{ date('Y-m-d', time() + 86400 * 30) }}",
    "itemCondition": "http://schema.org/UsedCondition",
    "availability": "http://schema.org/InStock",
    "url": "{{ $product->link() }}",
    "seller": {
      "@type": "Organization",
      "name": "{{ env('APP_NAME') }}"
    }
  }
  @if(!empty($variations))
  ,"hasVariant": [
    @foreach($product->variations as $i => $variation)
    {{ $i ? ',' : '' }}
    {
        "@type": "Product",
        "sku": "{{ $product->sku }}_{{ $variation->id }}",
        "image": "{{ $variation->file_id ? $variation->image->url() : $product->image->url() }}",
        "name": "{{ $product->name }}",
        @foreach($variation->attribute_values as $value)
        "{{ strtolower($value->attribute->name) }}": "{{ $value->name }}{{ $value->attribute->unit }}",
        @endforeach
        "offers": {
          "@type": "Offer",
          "url": "{{ $product->link() }}#{{ $variation->id }}",
          "priceCurrency": "GBP",
          "price": {{ $variation->price }},
          "itemCondition": "https://schema.org/NewCondition",
          "availability": "https://schema.org/{{ $variation->stock > 0 ? 'InStock' : 'OutOfStock' }}"
        }
      }
    @endforeach
  ]
  @endif
  @if(isset($reviews))
  @php
        $bestRating = 0;
        $sumRating = 0;
        $reviewCount = 0;
        foreach($reviews as $review){
            if($review->grade > $bestRating){
                $bestRating = $review->grade;
            }
            $sumRating += $review->grade;
            $reviewCount++;
        }
  @endphp
  @if($reviewCount > 0)
,
  "aggregateRating": {
    "@type": "AggregateRating",
    "worstRating": "1",
    "ratingValue": "{{ round($sumRating/$reviewCount, 2) }}",
      "bestRating": "{{ $bestRating }}",
      "reviewCount": "{{ $reviewCount }}"
  }
  @else
,
  "aggregateRating": {
    "@type": "AggregateRating",
    "worstRating": "1",
    "ratingValue": "5",
      "bestRating": "5",
      "reviewCount": "1"
  }
  @endif
@endif
}
</script>
