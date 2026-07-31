@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Menus'))
{{-- vendor style --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/extensions/dataTables.checkboxes.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">

    <link rel="stylesheet" type="text/css" href="{{asset('css/larchik/nav-menus.css')}}"/>
    <link rel="stylesheet" type="text/css" href="{{asset('css/larchik/edit.css')}}"/>
    <style>
        #menu-settings-column .accordion-container{
            border: none !important;
        }
        .accordion-section{
            border-radius: 0.267rem !important;
            margin-bottom: 0.71rem;
            border: 1px solid #464d5c;
        }
        .accordion-section.open, .accordion-section.open:hover, .accordion-section:hover{
            border: none;
        }
        .nav-menus-php #post-body, .accordion-section-content{
            background-color: #272e48;
        }
        .js .control-section.open .accordion-section-title{
            background-color: #272e48;
            color: #8a99b5;
        }
        .js .control-section.open .accordion-section-title::after{
            color: #8a99b5;
        }
        #menu-settings-column .accordion-container{
            border: 1px solid #464d5c;
        }
        .control-section.open .accordion-section-title {
            border-bottom: 1px solid #464d5c !important;
        }
        .control-section .accordion-section-title{
            border-radius: 0.267rem;
            background-color: #272e48;
            color: #8a99b5;
        }
        .js .control-section .accordion-section-title:focus, .js .control-section .accordion-section-title:hover, .js .control-section.open .accordion-section-title, .js .control-section:hover .accordion-section-title {
            color: #8a99b5;
            background-color: #272e48;
            border: 1px solid #272e48;
        }
        #menu-management{
            background-color: #272e48;
        }
        #nav-menu-header {
            border-bottom: 1px solid #464d5c !important;
        }
        .nav-menus-php #post-body{
            border: none;
        }
        .nav-menus-php .major-publishing-actions{
            line-height: 36px;
        }
        ul.add-menu-item-tabs li.tabs{
            background-color: #272e48;
            border-color: transparent;
        }
        .nav-tabs .nav-link{
            border: none !important;
        }
        .posttypediv div.tabs-panel, .taxonomydiv div.tabs-panel{
            border: solid 1px #1a233a;
            background-color: #1a233a;
        }
        #menu-to-edit .menu-item{
            border-radius: 0.267rem !important;
            margin-bottom: 0.71rem !important;
            border: 1px solid #464d5c;
        }
        .menu-item-bar{
            margin-top: 0;
        }
        .menu-item-bar .menu-item-handle{
            background: #272e48;
            border: none;
            border-radius: 0.267rem !important;
        }
        .menu-item-settings{
            background: #272e48;
            border: 1px solid #464d5c;
        }
        .link-to-original{
            border-color: #464d5c;
            width: calc(100% - 10px);
        }
        .spinner-border{
            display: none !important;
            margin-right: 20px;
            top: 4px;
            left: 10px;
            position: relative;
        }
        .spinner-border.is-active{
            display: block !important;
        }
    </style>
@endsection
@section('content')
    <h1 class="menus-title">{{ trans('locale.Menu settings') }}</h1>

    <section class="menu-edit" style="padding: 1.7rem 0;">
        @if(session('message-success'))
            <div class="alert alert-success">
                {{ session('message-success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @elseif(session('message-error'))
            <div class="alert alert-danger">
                {{ session('message-error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="nav-menus-php js" id="wpbody-content">
            <div class="panel-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>{{ $menu->name }}</h4>
                    </div>
                    <div class="panel-body">
                        <div id="nav-menus-frame" class="wp-clearfix">
                            <div id="menu-settings-column" class="metabox-holder">
                                <div class="clear"></div>
                                <form id="nav-menu-meta" class="nav-menu-meta" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="menu" id="nav-menu-meta-object-id" value="{{ $menu->id }}"/>
                                    <input type="hidden" name="action" value="add-menu-item"/>
                                    <div id="side-sortables" class="accordion-container">
                                        <ul class="outer-border">
                                            @php $i = 1; @endphp
                                            <li class="control-section accordion-section add-post-type-page open" id="add-post-type-page">
                                                <h3 class="accordion-section-title hndle" tabindex="0">
                                                    {{ trans('locale.Pages') }}
                                                </h3>
                                                <div class="accordion-section-content ">
                                                    <div class="inside">
                                                        @include('admin.menu.pages')
                                                    </div><!-- .inside -->
                                                </div><!-- .accordion-section-content -->
                                            </li><!-- .accordion-section -->
                                            <li class="control-section accordion-section add-category" id="add-category">
                                                <h3 class="accordion-section-title hndle" tabindex="0">
                                                    {{ trans('locale.Product categories') }}
                                                </h3>
                                                <div class="accordion-section-content ">
                                                    <div class="inside">
                                                        @include('admin.menu.categories')
                                                    </div><!-- .inside -->
                                                </div><!-- .accordion-section-content -->
                                            </li><!-- .accordion-section -->
                                            <li class="control-section accordion-section add-custom-links" id="add-custom-links">
                                                <h3 class="accordion-section-title hndle" tabindex="0">
                                                    {{ trans('locale.Custom links') }}
                                                </h3>
                                                <div class="accordion-section-content">
                                                    <div class="inside">
                                                        <div class="customlinkdiv" id="customlinkdiv">
                                                            <input type="hidden" value="custom" name="menu-item[-{{ $i }}][menu-item-type]"/>
                                                            <p id="menu-item-url-wrap" class="wp-clearfix">
                                                                <label class="howto" for="custom-menu-item-url">URL</label>
                                                                <input id="custom-menu-item-url" name="menu-item[-{{ $i }}][menu-item-url]" type="text" class="form-control form-control-sm code menu-item-textbox" value="http://" autocomplete="off"/>
                                                            </p>
                                                            <p id="menu-item-name-wrap" class="wp-clearfix">
                                                                <label class="howto" for="custom-menu-item-name">{{ trans('locale.Text') }}</label>
                                                                <input id="custom-menu-item-name" name="menu-item[-{{ $i }}][menu-item-title]" type="text" class="form-control form-control-sm regular-text menu-item-textbox" autocomplete="off"/>
                                                            </p>
                                                            <p class="button-controls wp-clearfix">
                                                                <span class="add-to-menu">
                                                                    <input type="submit" class="button submit-add-to-menu right btn btn-sm btn-primary" value="{{ trans('locale.Add to menu') }}" name="add-custom-menu-item" id="submit-customlinkdiv"/>
                                                                    <span class="spinner-border  spinner-grow-sm"></span>
                                                                </span>
                                                            </p>
                                                            @php $i++; @endphp
                                                        </div><!-- /.customlinkdiv -->
                                                    </div><!-- .inside -->
                                                </div><!-- .accordion-section-content -->
                                            </li><!-- .accordion-section -->
                                            <li class="control-section accordion-section add-custom-links" id="add-group">
                                                <h3 class="accordion-section-title hndle" tabindex="0">
                                                    {{ trans('locale.Link groups') }}
                                                </h3>
                                                <div class="accordion-section-content">
                                                    <div class="inside">
                                                        <div class="groupdiv" id="groupdiv">
                                                            <input type="hidden" value="group" name="menu-item[-{{ $i }}][menu-item-type]"/>
                                                            <p id="menu-item-name-wrap" class="wp-clearfix">
                                                                <label class="howto" for="custom-menu-item-name">{{ trans('locale.menus.Name') }}</label>
                                                                <input id="group-menu-item-name" name="menu-item[-{{ $i }}][menu-item-title]" type="text" class="form-control form-control-sm regular-text menu-item-textbox" autocomplete="off"/>
                                                            </p>
                                                            <p class="button-controls wp-clearfix">
                                                                <span class="add-to-menu">
                                                                    <input type="submit" class="button submit-add-to-menu right btn btn-sm btn-primary" value="{{ trans('locale.Add to menu') }}" name="add-custom-menu-item" id="submit-groupdiv"/>
                                                                    <span class="spinner-border  spinner-grow-sm"></span>
                                                                </span>
                                                            </p>
                                                            @php $i++; @endphp
                                                        </div><!-- /.customlinkdiv -->
                                                    </div><!-- .inside -->
                                                </div><!-- .accordion-section-content -->
                                            </li><!-- .accordion-section -->
                                        </ul><!-- .outer-border -->
                                    </div><!-- .accordion-container -->
                                </form>
                            </div><!-- /#menu-settings-column -->
                            <div id="menu-management-liquid">
                                <div id="menu-management">
                                    <form id="update-nav-menu" method="post" enctype="multipart/form-data">
                                        @csrf
                                        <div class="menu-edit">
                                            <input type="hidden" name="nav-menu-data">
                                            <input type="hidden" name="menu" id="menu" value="{{ $menu->id }}"/>
                                            <div id="nav-menu-header">
                                                <div class="major-publishing-actions wp-clearfix">
                                                    <label class="menu-name-label" for="menu-name" style="font-weight: normal; font-style: normal;">{{ trans('locale.menus.Menu title') }}</label>
                                                    <div class="js_langs" style="display: inline-block;">
                                                        @if(count($locales) > 1)
                                                            @foreach($locales as $locale)
                                                                <input name="menu-name_{{ $locale }}" id="menu-name{{ $locale != $main_lang ? '_'.$locale : '' }}" type="text" class="form-control menu-name regular-text menu-item-textbox js_lang lng_{{ $locale }}{{ $locale == $main_lang ? ' active_lang' : '' }}" value="{{ $menu->localize($locale, 'name') }}">
                                                            @endforeach
                                                        @else
                                                            <input name="menu-name" id="menu-name" type="text" class="form-control menu-name regular-text menu-item-textbox" value="{{ $menu->name }}">
                                                        @endif
                                                    </div>
                                                    <div class="publishing-action">
                                                        <input type="submit" name="save_menu" id="save_menu_header" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1 menu-save" value="{{ trans('locale.menus.Save menu') }}">
                                                    </div><!-- END .publishing-action -->
                                                </div><!-- END .major-publishing-actions -->
                                            </div>
                                            <div id="post-body" style="clear: both; padding-top: 25px;">
                                                <div id="post-body-content" class="wp-clearfix">
                                                    <h3 style="margin-top: 0;">{{ trans('locale.menus.Menu structure') }}</h3>
                                                    <div class="drag-instructions post-body-plain">
                                                        <p>{{ trans('locale.menus.Arrange the elements in the desired order by dragging and dropping You can also click the arrow to the right of an element to access additional settings') }}</p>
                                                    </div>
                                                    <div id="menu-instructions" class="post-body-plain menu-instructions-inactive"><p>{{ trans('locale.menus.Add menu items from the left column') }}</p></div>
                                                    <ul class="menu" id="menu-to-edit">
                                                        @include('admin.menu.items')
                                                    </ul>
                                                </div><!-- /#post-body-content -->
                                            </div><!-- /#post-body -->
                                            <div id="nav-menu-footer">
                                                <div class="major-publishing-actions wp-clearfix">
                                                    <div class="publishing-action">
                                                        <input type="submit" name="save_menu" id="save_menu_footer" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1 menu-save" value="{{ trans('locale.menus.Save menu') }}"/>
                                                    </div>
                                                    <!-- END .publishing-action -->
                                                </div><!-- END .major-publishing-actions -->
                                            </div><!-- /#nav-menu-footer -->
                                        </div><!-- /.menu-edit -->
                                    </form><!-- /#update-nav-menu -->
                                </div><!-- /#menu-management -->
                            </div><!-- /#menu-management-liquid -->
                        </div><!-- /#nav-menus-frame -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src='{{asset('js/larchik/accordion.js')}}'></script>
    <script src='{{asset('js/larchik/postbox.js')}}'></script>
    <script src='{{asset('js/larchik/nav-menu.js')}}'></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script type='text/javascript'>
        /* <![CDATA[ */
        var userSettings = {"url":"\/","uid":"1","time":"1653893553","secure":""};
        var wpAjax = {"noPerm":"\u0418\u0437\u0432\u0438\u043d\u0438\u0442\u0435, \u0432\u0430\u043c \u043d\u0435 \u0440\u0430\u0437\u0440\u0435\u0448\u0435\u043d\u043e \u0432\u044b\u043f\u043e\u043b\u043d\u044f\u0442\u044c \u0434\u0430\u043d\u043d\u043e\u0435 \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0435.","broken":"\u0427\u0442\u043e-\u0442\u043e \u043f\u043e\u0448\u043b\u043e \u043d\u0435 \u0442\u0430\u043a."};
        var postBoxL10n = {"postBoxEmptyString":"\u041f\u0435\u0440\u0435\u0442\u0430\u0449\u0438\u0442\u0435 \u0431\u043b\u043e\u043a\u0438 \u0441\u044e\u0434\u0430"};
        var navMenuL10n = {"noResultsFound":"\u0420\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u043e\u0432 \u043d\u0435 \u043d\u0430\u0439\u0434\u0435\u043d\u043e.","warnDeleteMenu":"\u0412\u044b \u0441\u043e\u0431\u0438\u0440\u0430\u0435\u0442\u0435\u0441\u044c \u043d\u0430\u0432\u0441\u0435\u0433\u0434\u0430 \u0443\u0434\u0430\u043b\u0438\u0442\u044c \u044d\u0442\u043e \u043c\u0435\u043d\u044e. \n \u00ab\u041e\u0442\u043c\u0435\u043d\u0430\u00bb \u2014 \u043e\u0441\u0442\u0430\u0432\u0438\u0442\u044c, \u00ab\u041e\u041a\u00bb \u2014 \u0443\u0434\u0430\u043b\u0438\u0442\u044c.","saveAlert":"\u0421\u0434\u0435\u043b\u0430\u043d\u043d\u044b\u0435 \u0432\u0430\u043c\u0438 \u0438\u0437\u043c\u0435\u043d\u0435\u043d\u0438\u044f \u0431\u0443\u0434\u0443\u0442 \u043e\u0442\u043c\u0435\u043d\u0435\u043d\u044b, \u0435\u0441\u043b\u0438 \u0432\u044b \u0443\u0439\u0434\u0451\u0442\u0435 \u0441 \u044d\u0442\u043e\u0439 \u0441\u0442\u0440\u0430\u043d\u0438\u0446\u044b.","untitled":"(\u0431\u0435\u0437 \u0442\u0435\u043a\u0441\u0442\u0430)"};
        var menus = {"oneThemeLocationNoMenus":"","moveUp":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u0432\u044b\u0448\u0435","moveDown":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043d\u0438\u0436\u0435","moveToTop":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043d\u0430\u0432\u0435\u0440\u0445","moveUnder":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043f\u043e\u0434 \u00ab%s\u00bb","moveOutFrom":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u0438\u0437-\u043f\u043e\u0434 \u00ab%s\u00bb","under":"\u041f\u043e\u0434 \u00ab%s\u00bb","outFrom":"\u0418\u0437-\u043f\u043e\u0434 \u00ab%s\u00bb","menuFocus":"%1$s. \u042d\u043b\u0435\u043c\u0435\u043d\u0442 \u043c\u0435\u043d\u044e %2$d \u0438\u0437 %3$d.","subMenuFocus":"%1$s. \u0414\u043e\u0447\u0435\u0440\u043d\u0438\u0439 \u044d\u043b\u0435\u043c\u0435\u043d\u0442 \u043d\u043e\u043c\u0435\u0440 %2$d \u044d\u043b\u0435\u043c\u0435\u043d\u0442\u0430 %3$s."};
        /* ]]> */

        (function($, window, undefined){
            var $document = $(document),
                $window = $(window),
                $body = $(document.body);

            // show/hide/save table columns
            columns = {
                init: function () {
                    var that = this;
                    $('.hide-column-tog', '#adv-settings').click(function () {
                        var $t = $(this), column = $t.val();
                        if ($t.prop('checked'))
                            that.checked(column);
                        else
                            that.unchecked(column);

                        columns.saveManageColumnsState();
                    });
                },

                saveManageColumnsState: function () {
                    var hidden = this.hidden();
                    $.post(ajaxurl, {
                        action: 'hidden-columns',
                        hidden: hidden,
                        screenoptionnonce: $('#screenoptionnonce').val(),
                        page: pagenow
                    });
                },

                checked: function (column) {
                    $('.column-' + column).removeClass('hidden');
                    this.colSpanChange(+1);
                },

                unchecked: function (column) {
                    $('.column-' + column).addClass('hidden');
                    this.colSpanChange(-1);
                },

                hidden: function () {
                    return $('.manage-column[id]').filter(':hidden').map(function () {
                        return this.id;
                    }).get().join(',');
                },

                useCheckboxesForHidden: function () {
                    this.hidden = function () {
                        return $('.hide-column-tog').not(':checked').map(function () {
                            var id = this.id;
                            return id.substring(id, id.length - 5);
                        }).get().join(',');
                    };
                },

                colSpanChange: function (diff) {
                    var $t = $('table').find('.colspanchange'), n;
                    if (!$t.length)
                        return;
                    n = parseInt($t.attr('colspan'), 10) + diff;
                    $t.attr('colspan', n.toString());
                }
            };

            $document.ready(function () {
                columns.init();
            });
        }(jQuery, window));
    </script>
    @include('admin.media.assets')
@endsection