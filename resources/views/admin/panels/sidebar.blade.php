{{-- vertical-menu --}}
@if($configData['mainLayoutType'] == 'vertical-menu')
    <div class="main-menu menu-fixed @if($configData['theme'] === 'light') {{"menu-light"}} @else {{'menu-dark'}} @endif menu-accordion menu-shadow"
         data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mr-auto">
                    <a class="navbar-brand" href="{{asset('/')}}">
                        <div class="brand-logo">
                            <svg class="properloud-logo" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 27 25"
                                 style="enable-background:new 0 0 27 25;width: 26px;margin-top: -10px;margin-left: 4px;"
                                 xml:space="preserve">
                            <style>
                                .st0 {
                                    fill: none;
                                    stroke: #F25733;
                                    stroke-linecap: round;
                                    stroke-linejoin: round;
                                    stroke-miterlimit: 10;
                                }

                                .properloud {
                                    width: max-content;
                                    color: #fff;
                                    text-decoration: none;
                                }

                                .properloud:hover .st1 {
                                    fill: #F25733;
                                    stroke: #F25733;
                                }

                                .properloud span {
                                    color: #939393;
                                    text-decoration: none;
                                    font-family: arial, sans-serif;
                                    font-size: 12px;
                                    fill: none;
                                    stroke: none;
                                    margin-right: 5px
                                }

                                .properloud:hover span {
                                    color: #939393;
                                    text-decoration: none;
                                    fill: none;
                                    stroke: none;
                                }
                            </style>
                                <path class="st0" d="M12.9,10l9.7-4.3c0.5-0.2,0.8-0.3,1-0.2C23.8,5.6,24,5.8,24,6.1c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.6,0.6-1,0.8
                              l-7.8,3.4v2.4l4.8-2.2c0.4-0.2,0.8-0.3,1-0.2c0.2,0.1,0.3,0.3,0.3,0.6c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.5,0.5-1,0.7L14.8,18v2.3"></path>
                                <path class="st0" d="M23.6,14.6c0,0,0,3.5,0,3.6c0,0.4-0.1,0.6-0.5,0.9c-0.1,0.1-0.2,0.1-0.4,0.2c-2.5,1.4-9.3,4.5-9.5,4.6
                              c-0.4,0.2-0.9,0.1-1.1,0c-0.4-0.2-0.7-0.3-0.9-0.7C10.9,22.7,11,22,11,21.4v-4.5v-2.1v-2.3L3.3,8.8c-0.5-0.2-0.8-0.5-1-0.8
                              C2,7.8,1.9,6.2,1.9,5.9c0-0.3,0.1-0.5,0.3-0.6c0.2-0.1,0.6,0,1,0.2l9.6,4.5"></path>
                                <path class="st0"
                                      d="M7.5,21.8c-0.3-0.1-4.4-2-4.5-2.2c-0.6-0.4-0.7-0.6-0.7-1.1c0-0.1-0.2-6.2-0.2-6.2"></path>
                                <path class="st0"
                                      d="M6.7,3.1C6.9,3,12,0.9,12,0.9c0.9-0.2,0.9-0.2,1.7,0c0,0,5.4,2,5.4,2"></path>
                            </svg>
                        </div>
                        <h2 class="brand-text mb-0">
                            @if(!empty($configData['templateTitle']) && isset($configData['templateTitle']))
                                {{$configData['templateTitle']}}
                            @else
                                Properloud
                            @endif
                        </h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle">
                    <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
                        <i class="bx bx-x d-block d-xl-none font-medium-4 primary"></i>
                        <i class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block primary"
                           data-ticon="bx-disc"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <div class="main-menu-content">
            <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation"
                data-icon-style="lines">
                @if(!empty($menuData[0]) && isset($menuData[0]))
                    @foreach ($menuData[0]->menu as $menu)
                        @if(isset($menu->navheader))
                            <li class="navigation-header"><span>{{ trans('locale.'.$menu->navheader) }}</span></li>
                        @else
                            <li class="nav-item {{(request()->is($menu->url.'*')) ? 'active' : '' }}">
                                <a href="@if(isset($menu->url)){{asset($menu->url)}}@endif" @if(isset($menu->newTab)){{"target=_blank"}}@endif>
                                    @if(isset($menu->icon))
                                        <i class="menu-livicon" data-icon="{{$menu->icon}}"></i>
                                    @endif
                                    @if(isset($menu->name))
                                        <span class="menu-title">{{ trans('locale.'.$menu->name) }}</span>
                                    @endif
                                    @if(isset($menu->tag))
                                        <span class="{{$menu->tagcustom}}">{{$menu->tag}}</span>
                                    @endif
                                </a>
                                @if(isset($menu->submenu))
                                    @include('admin.panels.sidebar-submenu',['menu' => $menu->submenu])
                                @endif
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
@endif
{{-- horizontal-menu --}}
@if($configData['mainLayoutType'] == 'horizontal-menu')
    <div class="header-navbar navbar-expand-sm navbar navbar-horizontal navbar-light navbar-without-dd-arrow
@if($configData['navbarType'] === 'navbar-static') {{'navbar-sticky'}} @endif" role="navigation"
         data-menu="menu-wrapper">
        <div class="navbar-header d-xl-none d-block">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mr-auto">
                    <a class="navbar-brand" href="{{asset('/')}}">
                        <div class="brand-logo">
                            <svg class="properloud-logo" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 27 25"
                                 style="enable-background:new 0 0 27 25;width: 26px;margin-top: -10px;margin-left: 4px;"
                                 xml:space="preserve">
                            <style>
                                .st0 {
                                    fill: none;
                                    stroke: #F25733;
                                    stroke-linecap: round;
                                    stroke-linejoin: round;
                                    stroke-miterlimit: 10;
                                }

                                .properloud {
                                    width: max-content;
                                    color: #fff;
                                    text-decoration: none;
                                }

                                .properloud:hover .st1 {
                                    fill: #F25733;
                                    stroke: #F25733;
                                }

                                .properloud span {
                                    color: #939393;
                                    text-decoration: none;
                                    font-family: arial, sans-serif;
                                    font-size: 12px;
                                    fill: none;
                                    stroke: none;
                                    margin-right: 5px
                                }

                                .properloud:hover span {
                                    color: #939393;
                                    text-decoration: none;
                                    fill: none;
                                    stroke: none;
                                }
                            </style>
                                <path class="st0" d="M12.9,10l9.7-4.3c0.5-0.2,0.8-0.3,1-0.2C23.8,5.6,24,5.8,24,6.1c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.6,0.6-1,0.8
                              l-7.8,3.4v2.4l4.8-2.2c0.4-0.2,0.8-0.3,1-0.2c0.2,0.1,0.3,0.3,0.3,0.6c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.5,0.5-1,0.7L14.8,18v2.3"></path>
                                <path class="st0" d="M23.6,14.6c0,0,0,3.5,0,3.6c0,0.4-0.1,0.6-0.5,0.9c-0.1,0.1-0.2,0.1-0.4,0.2c-2.5,1.4-9.3,4.5-9.5,4.6
                              c-0.4,0.2-0.9,0.1-1.1,0c-0.4-0.2-0.7-0.3-0.9-0.7C10.9,22.7,11,22,11,21.4v-4.5v-2.1v-2.3L3.3,8.8c-0.5-0.2-0.8-0.5-1-0.8
                              C2,7.8,1.9,6.2,1.9,5.9c0-0.3,0.1-0.5,0.3-0.6c0.2-0.1,0.6,0,1,0.2l9.6,4.5"></path>
                                <path class="st0"
                                      d="M7.5,21.8c-0.3-0.1-4.4-2-4.5-2.2c-0.6-0.4-0.7-0.6-0.7-1.1c0-0.1-0.2-6.2-0.2-6.2"></path>
                                <path class="st0"
                                      d="M6.7,3.1C6.9,3,12,0.9,12,0.9c0.9-0.2,0.9-0.2,1.7,0c0,0,5.4,2,5.4,2"></path>
                            </svg>
                        </div>
                        <h2 class="brand-text mb-0">
                            @if(!empty($configData['templateTitle']) && isset($configData['templateTitle']))
                                {{$configData['templateTitle']}}
                            @else
                                Properloud
                            @endif
                        </h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle">
                    <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
                        <i class="bx bx-x d-block d-xl-none font-medium-4 primary toggle-icon"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <!-- Horizontal menu content-->
        <div class="navbar-container main-menu-content" data-menu="menu-container">
            <ul class="nav navbar-nav" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="filled">
                @if(!empty($menuData[1]) && isset($menuData[1]))
                    @foreach ($menuData[1]->menu as $menu)
                        <li class="@if(isset($menu->submenu)){{'dropdown'}} @endif nav-item" data-menu="dropdown">
                            <a class="@if(isset($menu->submenu)){{'dropdown-toggle'}} @endif nav-link"
                               href="{{asset($menu->url)}}"
                            @if(isset($menu->submenu)){{'data-toggle=dropdown'}} @endif @if(isset($menu->newTab)){{"target=_blank"}}@endif>
                                <i class="menu-livicon" data-icon="{{$menu->icon}}"></i>
                                <span>{{ __('locale.'.$menu->name)}}</span>
                            </a>
                            @if(isset($menu->submenu))
                                @include('panels.sidebar-submenu',['menu'=>$menu->submenu])
                            @endif
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
        <!-- /horizontal menu content-->
    </div>
@endif

{{-- vertical-box-menu --}}
@if($configData['mainLayoutType'] == 'vertical-menu-boxicons')
    <div class="main-menu menu-fixed @if($configData['theme'] === 'light') {{"menu-light"}} @else {{'menu-dark'}} @endif menu-accordion menu-shadow"
         data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mr-auto">
                    <a class="navbar-brand" href="{{asset('/')}}">
                        <div class="brand-logo">
                            <svg class="properloud-logo" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 27 25"
                                 style="enable-background:new 0 0 27 25;width: 26px;margin-top: -10px;margin-left: 4px;"
                                 xml:space="preserve">
                            <style>
                                .st0 {
                                    fill: none;
                                    stroke: #F25733;
                                    stroke-linecap: round;
                                    stroke-linejoin: round;
                                    stroke-miterlimit: 10;
                                }

                                .properloud {
                                    width: max-content;
                                    color: #fff;
                                    text-decoration: none;
                                }

                                .properloud:hover .st1 {
                                    fill: #F25733;
                                    stroke: #F25733;
                                }

                                .properloud span {
                                    color: #939393;
                                    text-decoration: none;
                                    font-family: arial, sans-serif;
                                    font-size: 12px;
                                    fill: none;
                                    stroke: none;
                                    margin-right: 5px
                                }

                                .properloud:hover span {
                                    color: #939393;
                                    text-decoration: none;
                                    fill: none;
                                    stroke: none;
                                }
                            </style>
                                <path class="st0" d="M12.9,10l9.7-4.3c0.5-0.2,0.8-0.3,1-0.2C23.8,5.6,24,5.8,24,6.1c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.6,0.6-1,0.8
                              l-7.8,3.4v2.4l4.8-2.2c0.4-0.2,0.8-0.3,1-0.2c0.2,0.1,0.3,0.3,0.3,0.6c0,0.3-0.1,1.8-0.3,2.1c-0.2,0.3-0.5,0.5-1,0.7L14.8,18v2.3"></path>
                                <path class="st0" d="M23.6,14.6c0,0,0,3.5,0,3.6c0,0.4-0.1,0.6-0.5,0.9c-0.1,0.1-0.2,0.1-0.4,0.2c-2.5,1.4-9.3,4.5-9.5,4.6
                              c-0.4,0.2-0.9,0.1-1.1,0c-0.4-0.2-0.7-0.3-0.9-0.7C10.9,22.7,11,22,11,21.4v-4.5v-2.1v-2.3L3.3,8.8c-0.5-0.2-0.8-0.5-1-0.8
                              C2,7.8,1.9,6.2,1.9,5.9c0-0.3,0.1-0.5,0.3-0.6c0.2-0.1,0.6,0,1,0.2l9.6,4.5"></path>
                                <path class="st0"
                                      d="M7.5,21.8c-0.3-0.1-4.4-2-4.5-2.2c-0.6-0.4-0.7-0.6-0.7-1.1c0-0.1-0.2-6.2-0.2-6.2"></path>
                                <path class="st0"
                                      d="M6.7,3.1C6.9,3,12,0.9,12,0.9c0.9-0.2,0.9-0.2,1.7,0c0,0,5.4,2,5.4,2"></path>
                            </svg>
                        </div>
                        <h2 class="brand-text mb-0">
                            @if(!empty($configData['templateTitle']) && isset($configData['templateTitle']))
                                {{$configData['templateTitle']}}
                            @else
                                Properloud
                            @endif
                        </h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"><i
                                class="bx bx-x d-block d-xl-none font-medium-4 primary toggle-icon"></i><i
                                class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block collapse-toggle-icon primary"
                                data-ticon="bx-disc"></i></a></li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <div class="main-menu-content">
            <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation"
                data-icon-style="">
                @if(!empty($menuData[2]) && isset($menuData[2]))
                    @foreach ($menuData[2]->menu as $menu)
                        @if(isset($menu->navheader))
                            <li class="navigation-header"><span>{{$menu->navheader}}</span></li>
                        @else
                            <li class="nav-item {{(request()->is($menu->url.'*')) ? 'active' : '' }}">
                                <a href="@if(isset($menu->url)){{asset($menu->url)}} @endif" @if(isset($menu->newTab)){{"target=_blank"}}@endif>
                                    @if(isset($menu->icon))
                                        <i class="{{$menu->icon}}"></i>
                                    @endif
                                    @if(isset($menu->name))
                                        <span class="menu-title">{{ __('locale.'.$menu->name)}}</span>
                                    @endif
                                    @if(isset($menu->tag))
                                        <span class="{{$menu->tagcustom}}">{{$menu->tag}}</span>
                                    @endif
                                </a>
                                @if(isset($menu->submenu))
                                    @include('panels.sidebar-submenu',['menu' => $menu->submenu])
                                @endif
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
@endif
