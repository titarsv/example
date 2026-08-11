@if(count($selected_filters) > 0)
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($selected_filters as $selected_filter)
            <span class="badge text-bg-light border js_remove_filter" data-id="{{ $selected_filter['id'] }}" data-type="{{ $selected_filter['type'] }}" role="button">
                {{ $selected_filter['name'] }} <i class="bi bi-x"></i>
            </span>
        @endforeach
        <span class="badge text-bg-secondary js_clear_filters" role="button">Сбросить всё</span>
    </div>
@endif
