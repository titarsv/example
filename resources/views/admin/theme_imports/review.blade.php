@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.theme_import.review_title', ['name' => $import->name]))
@section('content')
    <h1 class="pages-title">{{ trans('locale.theme_import.review_title', ['name' => $import->name]) }}</h1>

    @if(!empty($import->theme_name))
        <div class="alert alert-info">
            {!! trans('locale.theme_import.theme_created', ['theme' => $import->theme_name]) !!}
            <hr>
            <strong>{{ trans('locale.theme_import.activation_title') }}</strong>
            <ol class="mb-0">
                <li>{!! trans('locale.theme_import.activation_step_review', ['path' => 'resources/themes/'.$import->theme_name]) !!}</li>
                <li>{!! trans('locale.theme_import.activation_step_env', ['theme' => $import->theme_name]) !!}</li>
                <li>{{ trans('locale.theme_import.activation_step_available') }}</li>
                <li>{{ trans('locale.theme_import.activation_step_build') }}</li>
            </ol>
        </div>
    @endif

    @if(!empty($import->log))
        <div class="card mb-2">
            <div class="card-body">
                <h5 class="card-title">{{ trans('locale.theme_import.log') }}</h5>
                <ul class="mb-0">
                    @foreach($import->log as $entry)
                        <li>{{ $entry }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="theme-imports-review-wrapper">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ trans('locale.page_import.file') }}</th>
                    <th>{{ trans('locale.theme_import.page_type') }}</th>
                    <th>{{ trans('locale.page_import.status') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($import->built_pages ?? []) as $result)
                    <tr>
                        <td>{{ $result['file'] ?? '' }}</td>
                        <td>
                            <span class="badge badge-light">{{ trans('locale.theme_import.type_'.($result['type'] ?? 'static')) }}</span>
                            @if(($result['classified_by'] ?? '') === 'ai')
                                <i class="bx bx-bulb text-warning" data-toggle="tooltip" title="{{ trans('locale.theme_import.classified_by_ai') }}"></i>
                            @elseif(($result['classified_by'] ?? '') === 'default')
                                <i class="bx bx-help-circle text-muted" data-toggle="tooltip" title="{{ trans('locale.theme_import.classified_by_default') }}"></i>
                            @endif
                        </td>
                        <td>
                            @if(($result['status'] ?? '') === 'created' && !empty($result['page_id']))
                                @php($page = $pages[$result['page_id']] ?? null)
                                <span class="badge badge-{{ !empty($page) && $page->status ? 'success' : 'warning' }}">
                                    {{ !empty($page) && $page->status ? trans('locale.page_import.published') : trans('locale.page_import.created') }}
                                </span>
                            @elseif(($result['status'] ?? '') === 'created')
                                {{-- blog/article/catalog/search/404 — файл темы, не Page-запись
                                     (см. docs/dynamic-page-import-plan.md, шаги 2–3, 5) --}}
                                <span class="badge badge-success">{{ trans('locale.page_import.created') }}</span>
                            @elseif(($result['status'] ?? '') === 'skipped')
                                <span class="badge badge-light">{{ trans('locale.page_import.skipped') }}</span>
                            @elseif(($result['status'] ?? '') === 'not_implemented')
                                <span class="badge badge-secondary" title="{{ trans('locale.theme_import.not_implemented_hint') }}">
                                    {{ trans('locale.theme_import.not_implemented') }}
                                </span>
                            @else
                                <span class="badge badge-danger" title="{{ $result['error'] ?? '' }}">
                                    {{ trans('locale.page_import.error_'.($result['error'] ?? 'generic')) }}
                                </span>
                            @endif
                            @if(!empty($result['scss_placed']))
                                <span class="badge badge-light" title="{{ trans('locale.theme_import.scss_placed_hint') }}">SCSS</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($result['page_id']))
                                <a class="mr-2" href="/admin/pages/edit/{{ $result['page_id'] }}" target="_blank">
                                    <i class="bx bx-edit-alt" data-toggle="tooltip" title="{{ trans('locale.page_import.edit_page') }}"></i>
                                </a>
                                <a class="mr-2" href="/admin/pages/template/{{ $result['name'] }}" target="_blank">
                                    <i class="bx bx-code-alt" data-toggle="tooltip" title="{{ trans('locale.page_import.edit_template') }}"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ trans('locale.No results found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
@endsection