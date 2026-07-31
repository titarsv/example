<div class="js_mob_filters">
    <div class="catalog-filter__block active">
        <div class="catalog-filter__block-head">
            Sort by
            <i>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </i>
        </div>
        <div class="catalog-filter__block-body js_mob_sort">
            <span
                class="catalog-sort{{ request('order') == 'priority-asc' || empty(request('order')) ? ' current' : '' }}"
                data-value="priority-asc" data-sort="Default sorting">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                Default sorting
            </span>
            <span class="catalog-sort{{ request('order') == 'popularity-desc' ? ' current' : '' }}"
                data-value="popularity-desc" data-sort="by Popularity">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                by Popularity
            </span>
            <span class="catalog-sort{{ request('order') == '   ' ? ' current' : '' }}" data-value="rating-desc"
                data-sort="by Average rating">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                by Average rating
            </span>
            <span class="catalog-sort{{ request('order') == 'created-desc' ? ' current' : '' }}"
                data-value="created-desc" data-sort="by Latest">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                by Latest
            </span>
            <span class="catalog-sort{{ request('order') == 'price-asc' ? ' current' : '' }}" data-value="price-asc"
                data-sort="by Price: low to high">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                by Price: low to high
            </span>
            <span class="catalog-sort{{ request('order') == 'price-desc' ? ' current' : '' }}" data-value="price-desc"
                data-sort="by Price: high to low">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                        stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                by Price: high to low
            </span>
        </div>
    </div>
    @if(isset($filter['attributes']))
        @foreach($filter['attributes'] as $attribute_id => $attribute)
            <div class="catalog-filter__block active">
                <div class="catalog-filter__block-head">
                    {{ str_replace('THC %, %', 'THC, %', $attribute['name']) }}
                    <i>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </i>
                </div>
                <div class="catalog-filter__block-body">
                    @foreach($attribute['values'] as $value_id => $value)
                        <label class="catalog-filter" for="mob_a{{ $value_id }}" data-filter="{{ $value['name'] }}">
                            <input type="checkbox" class="js_mob_attribute_checkbox_filter hidden" style="display: none;"
                                name="attributes[]" value="{{ $value_id }}" id="mob_a{{ $value_id }}" data-id="{{ $value_id }}" {{ $value['checked'] ? ' checked' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                                    stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            {{ $value['name'] }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
    @if(isset($filter['is_sale']))
        <div class="catalog-filter__block active">
            <div class="catalog-filter__block-head">
                Products with promotions
                <i>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </i>
            </div>
            <div class="catalog-filter__block-body">
                <label for="js_mob_is_sale" class="catalog-filter" data-filter="On Sale">
                    <input type="checkbox" class="hidden" style="display: none;" name="is_sale" value="1"
                        id="js_mob_is_sale" {{ $filter['is_sale']['active'] ? ' checked' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
                            stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    On Sale
                </label>
            </div>
        </div>
    @endif
</div>