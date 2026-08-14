@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.theme_import.preview_title', ['type' => trans('locale.theme_import.type_'.$type), 'name' => $import->name]))
@section('content')
    <h1 class="pages-title">{{ trans('locale.theme_import.preview_title', ['type' => trans('locale.theme_import.type_'.$type), 'name' => $import->name]) }}</h1>

    <div class="alert alert-info">
        {{ trans('locale.theme_import.preview_hint') }}
    </div>

    @if($source === null)
        <div class="alert alert-warning mb-0">
            {{ trans('locale.theme_import.source_not_found') }}
        </div>
    @else
        <pre class="theme-imports-preview-source"><code>{{ $source }}</code></pre>
    @endif
@endsection
{{-- page styles --}}
@section('page-styles')
    <style>
        .theme-imports-preview-source {
            max-height: 75vh;
            overflow: auto;
            padding: 1rem;
            border-radius: 0.375rem;
            background: #1e1e1e;
            color: #d4d4d4;
            font-size: 0.85rem;
            line-height: 1.5;
        }
    </style>
@endsection