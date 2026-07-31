<header class="header">
    <div class="header-top">
        <div class="container">
            <img src="/images/face.webp" loading="lazy" alt="face" width="40" height="37">
            <span>Place your order by 4 PM — get it tomorrow!</span>
            <img src="/images/face.webp" loading="lazy" alt="face" width="40" height="37">>
        </div>
    </div>
    <div class="header-main">
        <div class="container">
            <a href="{{ base_url('/') }}" class="header-logo">
                <img src="/images/logo.svg" loading="lazy" alt="{{ env('APP_NAME') }}">
            </a>
            @if(!empty($main_menu))
                <ul class="header-menu">
                    @foreach($main_menu as $item)
                        <li{!! $item->class ? ' class="'.$item->class.'"' : '' !!}>
                           <a {!! $item->link_attributes !!}{!! !empty($item->active) ? ' class="active"' : '' !!}> {{ $item->name }}</a>
                            {!! $item->description !!}
                            @if($item->class && $item->class == 'mega-menu-btn')
                                <i>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12H19" stroke="#0B0B0B" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 5V19" stroke="#0B0B0B" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </i>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="header-search__btn">Search</div>
            <a href="javascript:void(0)" class="header-cart__btn">Cart (<span class="amount js_cart_counter">{{ $cart->total_quantity }}</span>)</a>
            <div class="mobile-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="16" viewBox="0 0 25 16" fill="none">
                    <path d="M1 8L24 8" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"/>
                    <path d="M1 15L24 15" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"/>
                    <path d="M1 1L24 1" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="16" viewBox="0 0 25 16" fill="none">
                    <path d="M19.5 1.5L6.5 14.5" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6.5 1.5L19.5 14.5" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>
    @if(!empty($main_menu) && !empty($main_menu[0]) && !empty($main_menu[0]->children))
        <nav class="mega-menu">
            @foreach($main_menu[0]->children as $group)
                <ul class="{{ $group->class }}">
                    <li>
                        @if($group->type == 'group')
                            <span>{{ $group->name }}</span>
                        @else
                            <a href="{{ $group->link == '#' ? 'javascript:void(0)' : $group->link }}">{{ $group->name }}</a>
                        @endif
                    </li>
                    @foreach($group->children as $item)
                        <li>
                            <a href="{{ $item->link == '#' ? 'javascript:void(0)' : $item->link }}"{!! !empty($item->active) ? ' class="active"' : '' !!}>
                                {{ $item->name }}
                                {!! $item->description !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    @endif
    <div class="header-search__wrapper">
        <div class="header-search">
            <form class="header-search__form" action="{{ base_url('/search') }}" method="GET" accept-charset="UTF-8">
                <div class="header-search__inner">
                    <button>
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                            <path d="M28 28L22.2134 22.2134" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.6667 25.3333C20.5577 25.3333 25.3333 20.5577 25.3333 14.6667C25.3333 8.77563 20.5577 4 14.6667 4C8.77563 4 4 8.77563 4 14.6667C4 20.5577 8.77563 25.3333 14.6667 25.3333Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <input type="search" name="text" data-autocomplete="input-search" autocomplete="off">
                    <div class="search-close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                            <path d="M24 8L8 24" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 8L24 24" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="search-results" data-output="search-results"></div>
            </form>
        </div>
    </div>
</header>

<div class="mobile-menu">

    <div class="mobile-menu__inner">
        <div class="container">
            <div class="header-search__wrapper header-search-mobile__wrapper">
                <div class="header-search">
                    <form class="header-search__form" action="{{ base_url('/search') }}" method="GET" accept-charset="UTF-8">
                        <div class="header-search__inner">
                            <button>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                    <path d="M28 28L22.2134 22.2134" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.6667 25.3333C20.5577 25.3333 25.3333 20.5577 25.3333 14.6667C25.3333 8.77563 20.5577 4 14.6667 4C8.77563 4 4 8.77563 4 14.6667C4 20.5577 8.77563 25.3333 14.6667 25.3333Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <input type="search" name="text" placeholder="Search" data-autocomplete="input-search" autocomplete="off">
                            <div class="search-close">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                    <path d="M24 8L8 24" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 8L24 24" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                        <div class="search-results" data-output="search-results"></div>
                    </form>
                </div>
            </div>
            <?php
                function current_path(): string {
                    $uri = $_SERVER['REQUEST_URI'] ?? '/';
                    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
                    return rtrim($path, '/') ?: '/';
                }

                function menu_li(string $label, string $href, bool $allowHtml = false): string {
                    $cur = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
                    $hrefPath = rtrim(parse_url($href, PHP_URL_PATH), '/') ?: '/';

                    $isCurrent = ($cur === $hrefPath);

                    $labelOutput = $allowHtml ? $label : htmlspecialchars($label, ENT_QUOTES);

                    if ($isCurrent) {
                        return "<li><span>{$labelOutput}</span></li>";
                    }

                    return "<li><a href=\"{$href}\">{$labelOutput}</a></li>";
                }
            ?>
            <div class="mobile-menu__lists">
                <ul class="mobile-menu__list">
                    <?= menu_li('Shop All', '/catalog'); ?>
                    <?= menu_li('New Arrivals', '/new-arrivals'); ?>
                    <?= menu_li('On Sale <span>up to 25% Off</span>', '/on-sale', true); ?>
                    <?= menu_li('Disposable Vape', '/disposable-vape'); ?>
<!--                    --><?php //= menu_li('Vape Cartridges', '/vape-cartridges'); ?>
                    <?= menu_li('Edibles', '/edibles'); ?>
<!--                    --><?php //= menu_li('Concentrates', '/concentrates'); ?>
<!--                    --><?php //= menu_li('Accessories', '/accessories'); ?><!---->
                </ul>

                <ul class="mobile-menu__list">
                    <?php //= menu_li('About us', '/about'); ?>
                    <?= menu_li('Contact us', '/contacts'); ?>
                    <?= menu_li('FAQ', '/faq'); ?>
                    <?= menu_li('Blog', '/blog'); ?>
                    <?= menu_li('Customer Reviews', '/reviews'); ?>
                    <?= menu_li('Find Us in Telegram', '/telegram'); ?>
                    <?= menu_li('Pay with Cryptocurrency', '/cryptocurrency'); ?>
                </ul>

                {{--<ul class="mobile-menu__list">
                    <li><span>Shop All</span></li>
                    <li><a href="javascript:void(0)">New Arrivals</a></li>
                    <li><a href="javascript:void(0)">On Sale <span>up to 25% Off</span></a></li>
                    <li><a href="javascript:void(0)">Disposable Vape</a></li>
                    <li><a href="javascript:void(0)">Vape Cartridges</a></li>
                    <li><a href="javascript:void(0)">Edibles</a></li>
                    <li><a href="javascript:void(0)">Concentrates</a></li>
                    <li><a href="javascript:void(0)">Accessories</a></li>
                </ul>
                <ul class="mobile-menu__list">
                    <li><a href="javascript:void(0)">About us</a></li>
                    <li><a href="javascript:void(0)">Contact us</a></li>
                    <li><a href="javascript:void(0)">FAQ</a></li>
                    <li><a href="javascript:void(0)">Blog</a></li>
                    <li><a href="javascript:void(0)">Customer Reviews</a></li>
                    <li><a href="javascript:void(0)">Find Us in Telegram</a></li>
                    <li><a href="javascript:void(0)">Pay with Cryptocurrency</a></li>
                </ul>--}}
            </div>
        </div>
        <div class="mobile-menu__bottom">
            <?php if (current_path() === '/tracking'): ?>
            <span class="btn">Track Your Order</span>
            <?php else: ?>
            <a href="/tracking" class="btn">Track Your Order</a>
            <?php endif; ?>
        </div>
        {{--<div class="mobile-menu__bottom">
            <a href="/tracking" class="btn">Track Your Order</a>
        </div>--}}
    </div>
</div>

<div class="header-spacer" id="top"></div>
