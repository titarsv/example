@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.page_import.review_title', ['name' => $import->name]))
@section('content')
    <h1 class="pages-title">{{ trans('locale.page_import.review_title', ['name' => $import->name]) }}</h1>
    <section class="page-imports-review-wrapper">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ trans('locale.page_import.file') }}</th>
                    <th>{{ trans('locale.page_import.status') }}</th>
                    <th>{{ trans('locale.Name') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($import->imported_pages ?? []) as $result)
                    <tr>
                        <td>{{ $result['file'] ?? '' }}</td>
                        <td>
                            @if(($result['status'] ?? '') === 'created')
                                @php($page = $pages[$result['page_id']] ?? null)
                                <span class="badge badge-{{ !empty($page) && $page->status ? 'success' : 'warning' }}">
                                    {{ !empty($page) && $page->status ? trans('locale.page_import.published') : trans('locale.page_import.created') }}
                                </span>
                            @elseif(($result['status'] ?? '') === 'skipped')
                                <span class="badge badge-light">{{ trans('locale.page_import.skipped') }}</span>
                            @else
                                <span class="badge badge-danger" title="{{ $result['error'] ?? '' }}">
                                    {{ trans('locale.page_import.error_'.($result['error'] ?? 'generic')) }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $result['title'] ?? '—' }}</td>
                        <td>
                            @if(($result['status'] ?? '') === 'created')
                                <a class="mr-2" href="/admin/pages/edit/{{ $result['page_id'] }}" target="_blank">
                                    <i class="bx bx-edit-alt" data-toggle="tooltip" title="{{ trans('locale.page_import.edit_page') }}"></i>
                                </a>
                                <a class="mr-2" href="/admin/pages/template/{{ $result['name'] }}" target="_blank">
                                    <i class="bx bx-code-alt" data-toggle="tooltip" title="{{ trans('locale.page_import.edit_template') }}"></i>
                                </a>
                                @php($page = $pages[$result['page_id']] ?? null)
                                @if(empty($page) || !$page->status)
                                    <button type="button" class="btn btn-sm btn-primary js_publish_page" data-page-id="{{ $result['page_id'] }}">
                                        {{ trans('locale.page_import.publish') }}
                                    </button>
                                @endif
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
{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script>window.pageImportId = {{ $import->id }};</script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/page_imports_review.js')}}"></script>
@endsection