@extends('public.layouts.main')
@section('meta')
    <title>Сравнение товаров | {{ env('APP_NAME') }}</title>
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Сравнение товаров</h1>

        @if($groups->isEmpty())
            <div class="text-center py-4">
                <i class="bi bi-arrow-left-right display-4 text-muted mb-3"></i>
                <p class="text-muted">Список сравнения пуст. Добавляйте товары из каталога — значок со стрелками на карточке товара.</p>
                <a href="{{ base_url('/catalog') }}" class="btn btn-primary">Перейти в каталог</a>
            </div>
        @else
            @if($groups->count() > 1)
                <ul class="nav nav-pills mb-4">
                    @foreach($groups as $categoryId => $group)
                        @php($groupName = !empty($group['category']) ? $group['category']->name : null)
                        <li class="nav-item">
                            <a class="nav-link{{ $activeCategoryId === $categoryId ? ' active' : '' }}" href="{{ url_path('/compare') }}?category={{ $categoryId }}">
                                {{ $groupName ?: 'Без категории' }}
                                <span class="badge text-bg-light text-dark ms-1">{{ $group['products']->count() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($activeGroup)
                @php($products = $activeGroup['products'])
                @php($activeCategoryName = $activeGroup['category'] ? $activeGroup['category']->name : 'Без категории')
                <div class="js-compare-wrapper" data-category="{{ $activeCategoryId }}" data-category-name="{{ $activeCategoryName }}">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-compare-clear" data-category="{{ $activeCategoryId }}">
                            <i class="bi bi-x-lg"></i> Очистить список
                        </button>
                        @if($activeGroup['category'])
                            <a href="{{ $activeGroup['category']->link() }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-plus-lg"></i> Добавить товары
                            </a>
                        @endif
                        {{--
                            Повторяет выпадающее меню "Поделиться сторінкою" со страницы
                            сравнения elmir.ua: мессенджеры — обычные share-ссылки с текущим
                            URL; "Скопировать таблицу"/"для ИИ" — не отдельный запрос на
                            сервер, а сбор уже отрендеренной таблицы прямо из DOM в JS
                            (buildCompareRows/buildCompareAiData в app.js).
                        --}}
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-share"></i> Поделиться страницей
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item js-compare-share-link" href="#" data-network="telegram"><i class="bi bi-telegram"></i> Telegram</a></li>
                                <li><a class="dropdown-item js-compare-share-link" href="#" data-network="whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</a></li>
                                <li><a class="dropdown-item js-compare-share-link" href="#" data-network="facebook"><i class="bi bi-facebook"></i> Facebook</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item js-compare-copy-link" href="#"><i class="bi bi-link-45deg"></i> Скопировать ссылку</a></li>
                                <li><a class="dropdown-item js-compare-copy-table" href="#"><i class="bi bi-table"></i> Скопировать таблицу</a></li>
                                <li><a class="dropdown-item js-compare-copy-ai" href="#"><i class="bi bi-robot"></i> Скопировать для ИИ</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-compare-fullscreen">
                            <i class="bi bi-arrows-fullscreen"></i> На весь экран
                        </button>
                        <div class="form-check ms-auto">
                            <input type="checkbox" class="form-check-input js-compare-diff-only" id="compareDiffOnly">
                            <label class="form-check-label" for="compareDiffOnly">Только отличия</label>
                        </div>
                    </div>

                    {{--
                        Раскладка сравнения — CSS grid, не <table>. Шапка (картинка/
                        название/цена/кнопки) и тело таблицы — ДВА НЕЗАВИСИМЫХ
                        горизонтально прокручиваемых блока (.js-compare-sticky и
                        .js-compare-scroll), а не один общий overflow-x:auto контейнер
                        на двоих: у overflow-x:auto есть побочный эффект — по спеке CSS
                        Overflow, если overflow-x задан не visible, а overflow-y оставлен
                        visible, браузер сам переводит overflow-y тоже в auto. Контейнер
                        тем самым становится "скролл-контейнером" по вертикали — и именно
                        он, а не окно, становится точкой отсчёта (containing block) для
                        position:sticky внутри него. Раз сам этот контейнер вертикально
                        никогда не скроллится (скроллится страница), sticky внутри него
                        вообще перестаёт закрепляться — просто едет вместе со страницей.
                        Решение: шапка держит СОБСТВЕННЫЙ overflow-x:auto и является
                        sticky сама по себе (без промежуточного скролл-контейнера над
                        ней) — тогда точка отсчёта для неё это окно, как и нужно. Тело
                        таблицы скроллится по X отдельно; их прокрутка синхронизируется
                        в JS (app.js, updateCompareStickyOffset рядом).
                    --}}
                    <div class="js-compare-sticky" style="display: grid; grid-template-columns: 160px repeat({{ $products->count() }}, 200px); overflow-x: auto;">
                        <div></div>
                        @foreach($products as $index => $product)
                            <div class="js-compare-header-cell text-center px-2 pb-2" data-id="{{ $product->id }}" data-index="{{ $index }}" data-price="{{ $product->actual_price }}" data-url="{{ $product->link() }}">
                                <div class="position-relative">
                                    <button type="button" class="btn-close position-absolute top-0 end-0 m-2 js-compare-toggle" data-id="{{ $product->id }}" aria-label="Убрать из сравнения"></button>
                                    <div class="d-flex justify-content-center gap-1 mb-1">
                                        <button type="button" class="btn btn-sm btn-light js-compare-move-left{{ $index === 0 ? ' invisible' : '' }}" aria-label="Сдвинуть влево"><i class="bi bi-chevron-left"></i></button>
                                        <button type="button" class="btn btn-sm btn-light js-compare-move-right{{ $index === $products->count() - 1 ? ' invisible' : '' }}" aria-label="Сдвинуть вправо"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                    <a href="{{ $product->link() }}" class="d-block text-body text-decoration-none">
                                        {!! !empty($product->image) ? $product->image->image([120, 120], ['alt' => $product->name, 'loading' => 'lazy', 'class' => 'img-fluid mb-2'], 'cover') : '<img src="/images/larchik/no_image.jpg" class="img-fluid mb-2" alt="Нет фото" style="max-width:120px">' !!}
                                        <div class="small fw-normal">{{ $product->name }}</div>
                                    </a>
                                    <div class="fw-bold mt-1">
                                        @if($product->actual_price > 0)
                                            ₽{{ $product->actual_price }}
                                        @endif
                                    </div>
                                    @if(module_active('cart_checkout'))
                                        <button type="button" class="btn btn-primary btn-sm mt-1 js-add-to-cart" data-id="{{ $product->id }}">В корзину</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="js-compare-scroll" style="overflow-x: auto;">
                        <div class="js-compare-grid" style="display: grid; grid-template-columns: 160px repeat({{ $products->count() }}, 200px);">

                            <div class="js-compare-label text-muted small px-2 py-2 border-bottom text-nowrap">Наличие</div>
                            @foreach($products as $product)
                                <div class="js-compare-cell text-center px-2 py-2 border-bottom">
                                    @if($product->stock > 0)
                                        <span class="text-success"><i class="bi bi-check-circle"></i> В наличии</span>
                                    @elseif($product->stock < 0)
                                        <span class="text-warning"><i class="bi bi-clock-history"></i> Под заказ</span>
                                    @else
                                        <span class="text-danger"><i class="bi bi-x-circle"></i> Нет в наличии</span>
                                    @endif
                                </div>
                            @endforeach

                            @php($sku = $products->first()?->sku)
                            @if(!empty($sku))
                                <div class="js-compare-label text-muted small px-2 py-2 border-bottom text-nowrap">Код товара</div>
                                @foreach($products as $product)
                                    <div class="js-compare-cell text-center px-2 py-2 border-bottom">{{ $product->sku }}</div>
                                @endforeach
                            @endif

                            @forelse($rows as $row)
                                @php($differsClass = $row['differs'] ? ' js-row-differs bg-warning-subtle' : '')
                                <div class="js-compare-label text-muted small px-2 py-2 border-bottom text-nowrap{{ $differsClass }}">{{ $row['name'] }}</div>
                                @foreach($products as $product)
                                    <div class="js-compare-cell text-center px-2 py-2 border-bottom{{ $differsClass }}">{{ $row['cells'][$product->id] ?? '—' }}</div>
                                @endforeach
                            @empty
                                <div class="js-compare-label px-2 py-4"></div>
                                <div class="text-center text-muted px-2 py-4" style="grid-column: 2 / -1">
                                    У выбранных товаров нет общих характеристик для сравнения.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <p class="text-muted">Выберите категорию выше, чтобы увидеть таблицу сравнения.</p>
            @endif
        @endif
    </div>
@endsection