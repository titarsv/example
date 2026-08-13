<script type='application/ld+json'>
{
  "@context": "https://schema.org",
  "@type": "ImageObject",
  "author": "{{ !empty($image->owner) ? $image->owner->full_name : '' }}",
  "contentUrl": "{{ $image->url() }}",
  "datePublished": "{{ date('Y-m-d', strtotime($image->created_at)) }}",
  "description": "{{ $image->description }}",
  "name": "{{ $image->title }}"
}
</script>