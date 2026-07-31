<div id="posttype-page" class="posttypediv">
    <ul id="posttype-page-tabs" class="nav nav-tabs" role="tablist">
        <li class="nav-item{{ empty($page_tab) ? ' current' : '' }}">
            <a type="button" data-type="tabs-panel-posttype-page-most-recent" class="nav-tab-link nav-link d-flex align-items-center btn-sm{{ empty($page_tab) ? ' active' : '' }}">{{ trans('locale.menus.New') }}</a>
        </li>
        <li class="nav-item{{ !empty($page_tab) && $page_tab == 'all' ? ' current' : '' }}">
            <a type="button" data-type="page-all" class="nav-tab-link nav-link d-flex align-items-center btn-sm{{ !empty($page_tab) && $page_tab == 'all' ? ' active' : '' }}">{{ trans('locale.menus.All') }}</a>
        </li>
        <li class="nav-item">
            <a type="button" data-type="tabs-panel-posttype-page-search" class="nav-tab-link nav-link d-flex align-items-center btn-sm">{{ trans('locale.menus.Search') }}</a>
        </li>
    </ul><!-- .posttype-tabs -->

    <div id="tabs-panel-posttype-page-most-recent" class="tabs-panel tabs-panel-{{ empty($page_tab) ? 'active' : 'inactive' }}">
        <ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
            @include('admin.menu.pages_list', ['items' => $new_pages, 'i' => $i, 'key' => 'new'])
        </ul>
    </div><!-- /.tabs-panel -->

    <div class="tabs-panel tabs-panel-inactive" id="tabs-panel-posttype-page-search">
        <p class="quick-search-wrap">
            <label for="quick-search-posttype-page" class="screen-reader-text">{{ trans('locale.menus.Search') }}</label>
            <input type="search" class="form-control form-control-sm quick-search" value="" name="quick-search-posttype-page" id="quick-search-posttype-page" style="width: 190px;float: left;margin-bottom: 13px;"/>
            <span class="spinner-border spinner-grow-sm"></span>
            <input type="submit" name="submit" id="submit-quick-search-posttype-page" class="button button-small quick-search-submit hide-if-js" value="{{ trans('locale.menus.Search') }}"/>
        </p>
        <ul id="page-search-checklist" data-wp-lists="list:page" class="categorychecklist form-no-clear"></ul>
    </div><!-- /.tabs-panel -->

    <div id="page-all" class="tabs-panel tabs-panel-view-all tabs-panel-{{ !empty($page_tab) && $page_tab == 'all' ? 'active' : 'inactive' }}">
        @if($all_pages->lastPage() > 1)
            <div class="add-menu-item-pagelinks">
                @if($all_categories->currentPage() > 1)
                    <a class="next page-numbers" href="/?page-tab=all&#038;paged={{ $all_categories->currentPage() - 1 }}&#038;item-type=post_type&#038;item-object=page">
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
                           href='/?page-tab=all&#038;paged={{ $p }}&#038;item-type=post_type&#038;item-object=page'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </a>
                    @endif
                @endfor
                @if($all_categories->lastPage() > $all_categories->currentPage())
                    <a class="next page-numbers" href="/?page-tab=all&#038;paged={{ $all_categories->currentPage() + 1 }}&#038;item-type=post_type&#038;item-object=page">
                        <span aria-label="{{ trans('locale.menus.Next page') }}">&rarr;</span>
                    </a>
                @endif
            </div>
        @endif
        <ul id="pagechecklist" data-wp-lists="list:page" class="categorychecklist form-no-clear">
            @include('admin.menu.pages_list', ['items' => $all_pages, 'i' => $i, 'key' => 'all'])
        </ul>
        @if($all_pages->lastPage() > 1)
            <div class="add-menu-item-pagelinks">
                @if($all_categories->currentPage() > 1)
                    <a class="next page-numbers" href="/?page-tab=all&#038;paged={{ $all_categories->currentPage() - 1 }}&#038;item-type=post_type&#038;item-object=page">
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
                           href='/?page-tab=all&#038;paged={{ $p }}&#038;item-type=post_type&#038;item-object=page'>
                            <span class="screen-reader-text">{{ trans('locale.menus.page') }}</span> {{ $p }}
                        </a>
                    @endif
                @endfor
                @if($all_categories->lastPage() > $all_categories->currentPage())
                    <a class="next page-numbers" href="/?page-tab=all&#038;paged={{ $all_categories->currentPage() + 1 }}&#038;item-type=post_type&#038;item-object=page">
                        <span aria-label="{{ trans('locale.menus.Next page') }}">&rarr;</span>
                    </a>
                @endif
            </div>
        @endif
    </div><!-- /.tabs-panel -->
    <p class="button-controls wp-clearfix">
        <span class="list-controls">
            <a href="/?page-tab=all&#038;selectall=1#posttype-page" class="select-all aria-button-if-js" style="color: #5A8DEE;">{{ trans('locale.menus.Select all') }}</a>
        </span>
        <span class="add-to-menu">
            <input type="submit" class="button submit-add-to-menu right btn btn-sm btn-primary" value="{{ trans('locale.Add to menu') }}" name="add-post-type-menu-item" id="submit-posttype-page"/>
            <span class="spinner-border spinner-grow-sm"></span>
        </span>
    </p>
</div><!-- /.posttypediv -->