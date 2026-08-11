{{-- Base theme uses one responsive filter panel (Bootstrap offcanvas on
     mobile, sidebar on desktop) — see layouts/filters.blade.php. This file
     stays only because CatalogController::filterAction() still renders it
     into the AJAX response; nothing in the theme reads that key. --}}
@include('public.layouts.filters')
