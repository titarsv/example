<div class="catalog-filters__results js_checked_filters{{ count($selected_filters) > 0 ? ' show' : '' }}">
    <span class="catalog-filter__clear-all js_clear_filters">
        Clear All
    </span>
    @foreach($selected_filters as $selected_filter)
        <span class="catalog-filter__clear js_remove_filter" data-id="{{ $selected_filter['id'] }}" data-type="{{ $selected_filter['type'] }}" data-clear="{{ $selected_filter['name'] }}">
          {{ $selected_filter['name'] }}
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M12 4L4 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M4 4L12 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </span>
    @endforeach
</div>
