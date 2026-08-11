<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
  @foreach ($breadcrumbs as $i => $breadcrumb)
    @if(!empty($breadcrumb->url))
      {{ $i>0?',':'' }}
      {
        "@type": "ListItem",
        "position": {{ $i+1 }},
        "item":
        {
          "@id": "{{ $breadcrumb->url }}",
          "name": "{{ $breadcrumb->title }}"
        }
      }
    @endif
  @endforeach
  ]
}
</script>