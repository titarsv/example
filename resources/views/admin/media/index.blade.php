<!DOCTYPE html>
{{-- pageConfigs variable pass to Helper's updatePageConfig function to update page configuration  --}}
@isset($pageConfigs)
  {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
  // confiData variable layoutClasses array in Helper.php file.
    $configData = Helper::applClasses();
@endphp

<html class="loading" lang="@if(session()->has('locale')){{session()->get('locale')}}@else{{$configData['defaultLanguage']}}@endif"
      data-textdirection="{{$configData['direction'] == 'rtl' ? 'rtl' : 'ltr' }}">
<!-- BEGIN: Head-->

<head>
  <meta  charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>File Manager Application</title>
  {{--    <link rel="apple-touch-icon" href="{{asset('images/ico/apple-icon-120.png')}}">--}}
  <link rel="shortcut icon" type="image/x-icon" href="{{asset('images/ico/favicon.ico')}}">

  {{-- Include core + vendor Styles --}}
  @include('admin.panels.styles')
  <link rel="stylesheet" type="text/css" href="{{asset('css/pages/app-file-manager.css')}}">
</head>
<!-- END: Head-->

@if(!empty($configData['mainLayoutType']) && isset($configData['mainLayoutType']))
  @if($configData['mainLayoutType'] === 'horizontal-menu')
    <!-- BEGIN: Body-->
    <body class="horizontal-layout horizontal-menu @if(isset($configData['navbarType']) && ($configData['navbarType'] !== "navbar-hidden") ){{$configData['navbarType']}} @else {{'navbar-sticky'}}@endif 2-columns
@if($configData['theme'] === 'dark'){{'dark-layout'}} @elseif($configData['theme'] === 'semi-dark'){{'semi-dark-layout'}} @else {{'light-layout'}} @endif
    @if($configData['isContentSidebar']=== true) {{'content-left-sidebar'}} @endif
    @if(isset($configData['footerType'])) {{$configData['footerType']}} @endif {{$configData['bodyCustomClass']}}
    @if($configData['isCardShadow'] === false){{'no-card-shadow'}}@endif"
          data-open="hover" data-menu="horizontal-menu" data-col="2-columns">

    <!-- BEGIN: Header-->
    @include('admin.panels.horizontal-navbar')
    <!-- END: Header-->

    <!-- BEGIN: Main Menu-->
    @include('admin.panels.sidebar')
    <!-- END: Main Menu-->

    <!-- BEGIN: Content-->
    <div class="app-content content">
      {{-- Application page structure --}}
      @if($configData['isContentSidebar'] === true)
        <div class="content-area-wrapper">
          <div class="app-file-files upload-php" style="width: 100%">
            <div id="wp-media-grid" data-search="">

            </div>
          </div>
        </div>
      @else
        {{-- others page structures --}}
        <div class="content-overlay"></div>
        <div class="content-wrapper">
          <div class="content-header row">
            @if($configData['pageHeader'] === true && isset($breadcrumbs))
              @include('admin.panels.breadcrumbs')
            @endif
          </div>
          <div class="content-body">
            <div class="app-file-files upload-php" style="width: 100%">
              <div id="wp-media-grid" data-search="">

              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
    <!-- END: Content-->
    @if($configData['isCustomizer'] === true && isset($configData['isCustomizer']))
      <!-- BEGIN: Customizer-->
      <div class="customizer d-none d-md-block">
        <a class="customizer-close" href="#"><i class="bx bx-x"></i></a>
        <a class="customizer-toggle" href="#"><i class="bx bx-cog bx bx-spin white"></i></a>
        @include('pages.customizer-content')
      </div>
      <!-- End: Customizer-->
    @endif
    <!-- demo chat-->
    <div class="widget-chat-demo">
      @include('admin.pages.widget-chat')
    </div>

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Footer-->
    @include('admin.panels.footer')
    <!-- END: Footer-->

    @include('admin.panels.scripts')
    @include('admin.media.assets', ['query_vars' => ['trash' => $is_trash]])
    <script src="{{asset('js/admin/file-manager.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/media-grid.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/media.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/svg-painter.js')}}"></script>
    </body>
    <!-- END: Body-->
  @else
    <!-- BEGIN: Body-->
    <body class="vertical-layout vertical-menu-modern 2-columns
@if($configData['isMenuCollapsed'] == true){{'menu-collapsed'}}@endif
    @if($configData['theme'] === 'dark'){{'dark-layout'}} @elseif($configData['theme'] === 'semi-dark'){{'semi-dark-layout'}} @else {{'light-layout'}} @endif
    @if($configData['isContentSidebar'] === true) {{'content-left-sidebar'}} @endif @if(isset($configData['navbarType'])){{$configData['navbarType']}}@endif
    @if(isset($configData['footerType'])) {{$configData['footerType']}} @endif
    {{$configData['bodyCustomClass']}}
    @if($configData['mainLayoutType'] === 'vertical-menu-boxicons'){{'boxicon-layout'}}@endif
    @if($configData['isCardShadow'] === false){{'no-card-shadow'}}@endif"
          data-open="click" data-menu="vertical-menu-modern" data-col="2-columns">

    <!-- BEGIN: Header-->
    @include('admin.panels.navbar')
    <!-- END: Header-->

    <!-- BEGIN: Main Menu-->
    @include('admin.panels.sidebar')
    <!-- END: Main Menu-->

    <!-- BEGIN: Content-->
    <div class="app-content content">
      {{-- Application page structure --}}
      @if($configData['isContentSidebar'] === true)
        <div class="content-area-wrapper">
          <div class="app-file-files upload-php" style="width: 100%">
            <div id="wp-media-grid" data-search="">

            </div>
          </div>
        </div>
      @else
        {{-- others page structures --}}
        <div class="content-overlay"></div>
        <div class="content-wrapper">
          <div class="content-header row">
            @if($configData['pageHeader']=== true && isset($breadcrumbs))
              @include('admin.panels.breadcrumbs')
            @endif
          </div>
          <div class="content-body">
            <div class="app-file-files upload-php" style="width: 100%">
              <div id="wp-media-grid" data-search="">

              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
    <!-- END: Content-->
    @if($configData['isCustomizer'] === true && isset($configData['isCustomizer']))
      <!-- BEGIN: Customizer-->
      <div class="customizer d-none d-md-block">
        <a class="customizer-close" href="#"><i class="bx bx-x"></i></a>
        <a class="customizer-toggle" href="#"><i class="bx bx-cog bx bx-spin white"></i></a>
        @include('pages.customizer-content')
      </div>
      <!-- End: Customizer-->

    @endif

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Footer-->
    @include('admin.panels.footer')
    <!-- END: Footer-->

    @include('admin.panels.scripts')
    @include('admin.media.assets', ['query_vars' => ['trash' => $is_trash]])
    <script src="{{asset('js/admin/file-manager.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/media-grid.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/media.js')}}"></script>
    <script src="{{asset('js/scripts/larchik/svg-painter.js')}}"></script>
    </body>
    <!-- END: Body-->
  @endif
@else
  {{-- if mainLaoutType is empty or not set then its print below line --}}
  <h1>{{'mainLayoutType Option is empty in config custom.php file.'}}</h1>
@endif

</html>