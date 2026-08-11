<div class="bg-dark text-white text-center py-1 small">
    Бесплатная доставка при заказе от 3000 ₽ — оформите заказ до 16:00 и получите его завтра
</div>

<nav class="navbar navbar-expand-lg bg-body sticky-top border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ base_url('/') }}">
            {{ !empty($settings->site_name) ? $settings->site_name : env('APP_NAME') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-label="Меню">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            @if(!empty($main_menu))
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @foreach($main_menu as $item)
                        @if(!empty($item->children))
                            <li class="nav-item dropdown{{ !empty($item->has_active) ? ' active' : '' }}">
                                <a class="nav-link dropdown-toggle{{ !empty($item->active) || !empty($item->has_active) ? ' active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $item->name }}</a>
                                <ul class="dropdown-menu">
                                    @foreach($item->children as $child)
                                        @if($child->type == 'group')
                                            <li><h6 class="dropdown-header">{{ $child->name }}</h6></li>
                                            @foreach($child->children ?? [] as $grandchild)
                                                <li><a class="dropdown-item ps-4{{ !empty($grandchild->active) ? ' active' : '' }}" {!! $grandchild->link_attributes ?? 'href="'.$grandchild->link.'"' !!}>{{ $grandchild->name }}</a></li>
                                            @endforeach
                                        @else
                                            <li><a class="dropdown-item{{ !empty($child->active) ? ' active' : '' }}" {!! $child->link_attributes ?? 'href="'.$child->link.'"' !!}>{{ $child->name }}</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link{{ !empty($item->active) ? ' active' : '' }}" {!! $item->link_attributes !!}>{{ $item->name }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            <form class="d-flex position-relative js-search-wrapper mb-2 mb-lg-0 me-lg-3" action="{{ base_url('/search') }}" method="GET" accept-charset="UTF-8">
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" name="text" class="form-control js-live-search" placeholder="Поиск по каталогу" autocomplete="off">
                </div>
                <div class="js-search-results dropdown-menu d-none w-100 mt-5 shadow"></div>
            </form>

            <div class="d-flex align-items-center gap-3">
                @if(module_active('wishlist'))
                    <a href="{{ base_url('/wishlist') }}" class="link-body-emphasis fs-5" title="Избранное">
                        <i class="bi bi-heart"></i>
                    </a>
                @endif

                @if(module_active('compare'))
                    <div class="position-relative">
                        <a href="{{ url_path('/compare') }}" class="link-body-emphasis fs-5" title="Сравнение товаров" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                            <i class="bi bi-arrow-left-right"></i>
                            <span class="badge text-bg-dark rounded-pill position-absolute top-0 start-100 translate-middle js-compare-count{{ empty($compare_count) ? ' d-none' : '' }}">
                                {{ $compare_count }}
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end js-compare-dropdown" style="min-width: 260px">
                            @forelse($compare_groups as $group)
                                <li class="d-flex align-items-center justify-content-between px-3 py-1 gap-2">
                                    <a href="{{ url_path('/compare') }}?category={{ $group['id'] }}" class="text-body text-decoration-none text-truncate">
                                        {{ $group['name'] }} <span class="text-muted">({{ $group['count'] }})</span>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 js-compare-clear" data-category="{{ $group['id'] }}" aria-label="Удалить группу">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-1 text-muted small">Список сравнения пуст</li>
                            @endforelse
                        </ul>
                    </div>
                @endif

                <a href="{{ base_url('/user') }}" class="link-body-emphasis fs-5" title="Личный кабинет">
                    <i class="bi bi-person"></i>
                </a>

                @if(module_active('cart_checkout'))
                    <button type="button" class="btn btn-primary position-relative" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                        <i class="bi bi-cart3"></i>
                        <span class="badge text-bg-dark rounded-pill position-absolute top-0 start-100 translate-middle js-cart-count{{ empty($cart->total_quantity) ? ' d-none' : '' }}">
                            {{ $cart->total_quantity }}
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</nav>
