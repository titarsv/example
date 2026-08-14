<div class="row">
    <div class="col-lg-3 mb-4">
        <button class="btn btn-outline-secondary d-lg-none w-100 mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#catalogFiltersOffcanvas">
            <i class="bi bi-sliders"></i> Фильтры и сортировка
        </button>

        <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="catalogFiltersOffcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Фильтры</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#catalogFiltersOffcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <form id="catalogFilters">
                    <input type="hidden" name="category" value="{{ $category->id ?? '' }}">
                    <input type="hidden" name="order" value="priority-asc">
                    <input type="hidden" name="page" value="1">

                    <div class="mb-3">
                        <label class="form-label small text-muted">Сортировка</label>
                        <select class="form-select js-catalog-sort-select">
                            <option value="priority-asc">По умолчанию</option>
                            <option value="popularity-desc">По популярности</option>
                            <option value="rating-desc">По рейтингу</option>
                            <option value="created-desc">Сначала новые</option>
                            <option value="price-asc">Сначала дешевле</option>
                            <option value="price-desc">Сначала дороже</option>
                        </select>
                    </div>

                    <div id="catalogFilterFields">
                        @include('public.layouts.filters')
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div id="catalogSelectedFilters">
            @include('public.layouts.selected_filters')
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted js-products-count">{{ trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()) }}</span>
        </div>

        <div id="catalogProducts">
            @include('public.layouts.products_list')
        </div>

        <div id="catalogPagination" class="mt-4">
            @include('public.layouts.pagination', ['paginator' => $products])
        </div>
    </div>
</div>