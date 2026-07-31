<div id="taxonomy-category" class="taxonomydiv">
    <ul id="taxonomy-category-tabs" class="nav nav-tabs" role="tablist">
        <li class="nav-item{{ empty($category_tab) ? ' current' : '' }}">
            <a type="button" class="nav-tab-link nav-link d-flex align-items-center btn-sm{{ empty($category_tab) ? ' active' : '' }}" data-type="tabs-panel-category-pop" href="javascript:void(0)">{{ trans('locale.menus.New') }}</a>
        </li>
        <li class="nav-item{{ !empty($category_tab) && $category_tab == 'all' ? ' current' : '' }}">
            <a type="button" class="nav-tab-link nav-link d-flex align-items-center btn-sm{{ !empty($category_tab) && $category_tab == 'all' ? ' active' : '' }}" data-type="tabs-panel-category-all" href="javascript:void(0)">{{ trans('locale.menus.All') }}</a>
        </li>
        <li class="nav-item">
            <a type="button" class="nav-tab-link nav-link d-flex align-items-center btn-sm" data-type="tabs-panel-search-taxonomy-category" href="javascript:void(0)">{{ trans('locale.menus.Search') }}</a>
        </li>
    </ul><!-- .taxonomy-tabs -->

    <div id="tabs-panel-category-pop" class="tabs-panel tabs-panel-{{ empty($category_tab) ? 'active' : 'inactive' }}">
        <ul id="categorychecklist-pop" class="categorychecklist form-no-clear">
            @include('admin.menu.categories_list', ['items' => $new_categories, 'i' => $i, 'key' => 'new'])
        </ul>
    </div><!-- /.tabs-panel -->

    <div id="tabs-panel-category-all" class="tabs-panel tabs-panel-view-all tabs-panel-{{ !empty($category_tab) && $category_tab == 'all' ? 'active' : 'inactive' }}">
        @if($all_categories->lastPage() > 1)
            <div class="add-menu-item-pagelinks">
                @if($all_categories->currentPage() > 1)
                    <a class="next page-numbers" href="/?category-tab=all&#038;paged={{ $all_categories->currentPage() - 1 }}&#038;item-type=taxonomy&#038;item-object=category">
                        <span aria-label="{{ trans('locale.menus.Previous page') }}">&larr;</span>
                    </a>
                @endif
                @for($p = 1; $p <= $all_categories->lastPage(); $p++)
                    @if($p == $all_categories->currentPage())
                        <span aria-current='page' class='page-numbers current'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </span>
                    @else
                        <a class='page-numbers'
                           href='/?category-tab=all&#038;paged={{ $p }}&#038;item-type=taxonomy&#038;item-object=category'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </a>
                    @endif
                @endfor
                @if($all_categories->lastPage() > $all_categories->currentPage())
                    <a class="next page-numbers" href="/?category-tab=all&#038;paged={{ $all_categories->currentPage() + 1 }}&#038;item-type=taxonomy&#038;item-object=category">
                        <span aria-label="{{ trans('locale.menus.Next page') }}">&rarr;</span>
                    </a>
                @endif
            </div>
        @endif
        <ul id="categorychecklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
            @include('admin.menu.categories_list', ['items' => $all_categories, 'i' => $i, 'key' => 'all'])
        </ul>
        @if($all_categories->lastPage() > 1)
            <div class="add-menu-item-pagelinks">
                @if($all_categories->currentPage() > 1)
                    <a class="next page-numbers" href="/?category-tab=all&#038;paged={{ $all_categories->currentPage() - 1 }}&#038;item-type=taxonomy&#038;item-object=category">
                        <span aria-label="{{ trans('locale.menus.Previous page') }}">&larr;</span>
                    </a>
                @endif
                @for($p = 1; $p <= $all_categories->lastPage(); $p++)
                    @if($p == $all_categories->currentPage())
                        <span aria-current='page' class='page-numbers current'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </span>
                    @else
                        <a class='page-numbers'
                           href='/?category-tab=all&#038;paged={{ $p }}&#038;item-type=taxonomy&#038;item-object=category'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </a>
                    @endif
                @endfor
                @if($all_categories->lastPage() > $all_categories->currentPage())
                    <a class="next page-numbers" href="/?category-tab=all&#038;paged={{ $all_categories->currentPage() + 1 }}&#038;item-type=taxonomy&#038;item-object=category">
                        <span aria-label="{{ trans('locale.menus.Next page') }}">&rarr;</span>
                    </a>
                @endif
            </div>
        @endif
    </div><!-- /.tabs-panel -->

    <div class="tabs-panel tabs-panel-inactive"
         id="tabs-panel-search-taxonomy-category">
        <p class="quick-search-wrap">
            <label for="quick-search-taxonomy-category" class="screen-reader-text">{{ trans('locale.menus.Search') }}</label>
            <input type="search" class="form-control form-control-sm quick-search" value="" name="quick-search-taxonomy-category" id="quick-search-taxonomy-category" style="width: 190px;float: left;margin-bottom: 13px;"/>
            <span class="spinner-border spinner-grow-sm"></span>
            <input type="submit" name="submit" id="submit-quick-search-taxonomy-category" class="button button-small quick-search-submit hide-if-js" value="{{ trans('locale.menus.Search') }}"/>
        </p>

        <ul id="category-search-checklist" data-wp-lists="list:category" class="categorychecklist form-no-clear"></ul>
    </div><!-- /.tabs-panel -->

    <p class="button-controls wp-clearfix">
        <span class="list-controls">
            <a href="/?category-tab=all&#038;selectall=1#taxonomy-category" class="select-all aria-button-if-js" style="color: #5A8DEE;">{{ trans('locale.menus.Select all') }}</a>
        </span>
        <span class="add-to-menu">
            <input type="submit" class="button submit-add-to-menu right btn btn-sm btn-primary" value="{{ trans('locale.Add to menu') }}" name="add-taxonomy-menu-item" id="submit-taxonomy-category"/>
            <span class="spinner-border spinner-grow-sm"></span>
        </span>
    </p>
</div><!-- /.taxonomydiv -->