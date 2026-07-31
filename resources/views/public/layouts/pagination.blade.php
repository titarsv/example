@if ($paginator->lastPage() > 1)
    <div class="pagination{{ empty($without_js) ? ' js_pagination' : '' }}">
        {{-- @if($paginator->currentPage() < $paginator->lastPage())--}}
            {{-- <div class="col-xs-12">--}}
                {{-- <a href="{{ $cp->url($paginator->url($paginator->currentPage()+1), $paginator->currentPage()+1) }}"
                    class="show-more-btn margin" id="#more_products">--}}
                    {{-- More--}}
                    {{-- </a>--}}
                {{-- </div>--}}
            {{-- @endif--}}
            <ul class="pagination-numbers">
                @if($paginator->lastPage() <= 5)

                    @for ($c = 1; $c <= $paginator->lastPage(); $c++)
                        <li{!! $paginator->currentPage() == $c ? ' class="current"' : '' !!}>
                            @if($paginator->currentPage() == $c)
                                <a href="javascript:void(0)">{{ $c }}</a>
                            @else
                                <a href="{{ $cp->url($paginator->url($c), $c) }}" data-page="{{ $c }}">{{ $c }}</a>
                            @endif
                            </li>
                    @endfor

                @elseif($paginator->currentPage() < 4)

                        @for ($c = 1; $c <= 4; $c++)
                            <li{!! $paginator->currentPage() == $c ? ' class="current"' : '' !!}>
                                @if($paginator->currentPage() == $c)
                                    <a href="javascript:void(0)">{{ $c }}</a>
                                @else
                                    <a href="{{ $cp->url($paginator->url($c), $c) }}" data-page="{{ $c }}">{{ $c }}</a>
                                @endif
                                </li>
                        @endfor

                            @if($paginator->lastPage() >= 6)
                                <li class="dots"><a href="javascript:void(0)">...</a></li>
                            @endif

                            <li{!! $paginator->currentPage() == $paginator->lastPage() ? ' class="current"' : '' !!}>
                                @if($paginator->currentPage() == $paginator->lastPage())
                                    <a href="javascript:void(0)">{{ $paginator->lastPage() }}</a>
                                @else
                                    <a href="{{ $cp->url($paginator->url($paginator->lastPage()), $paginator->lastPage()) }}"
                                        data-page="{{ $paginator->lastPage() }}">{{ $paginator->lastPage() }}</a>
                                @endif
                                </li>

                    @elseif($paginator->currentPage() > ($paginator->lastPage() - 3))

                                <li{!! $paginator->currentPage() == 1 ? ' class="current"' : '' !!}>
                                    @if($paginator->currentPage() == 1)
                                        <a href="javascript:void(0)">{{ 1 }}</a>
                                    @else
                                        <a href="{{ $cp->url($paginator->url(1), 1) }}" data-page="1">{{ 1 }}</a>
                                    @endif
                                    </li>

                                    @if($paginator->lastPage() >= 4)
                                        <li class="dots"><a href="javascript:void(0)">...</a></li>
                                    @endif

                                    @for ($c = ($paginator->lastPage() - 3); $c <= $paginator->lastPage(); $c++)
                                        <li{!! $paginator->currentPage() == $c ? ' class="current"' : '' !!}>
                                            @if($paginator->currentPage() == $c)
                                                <span>{{ $c }}</span>
                                            @else
                                                <a href="{{ $cp->url($paginator->url($c), $c) }}" data-page="{{ $c }}">{{ $c }}</a>
                                            @endif
                                            </li>
                                    @endfor

                            @else

                                        <li{!! $paginator->currentPage() == 1 ? ' class="current"' : '' !!}>
                                            @if($paginator->currentPage() == 1)
                                                <a href="javascript:void(0)">{{ 1 }}</a>
                                            @else
                                                <a href="{{ $cp->url($paginator->url(1), 1) }}" data-page="1">{{ 1 }}</a>
                                            @endif
                                            </li>

                                            @if($paginator->currentPage() > 3)
                                                <li class="dots"><a href="javascript:void(0)">...</a></li>
                                            @endif

                                            @for ($c = ($paginator->currentPage() - 1); $c <= ($paginator->currentPage() + 1); $c++)
                                                <li{!! $paginator->currentPage() == $c ? ' class="current"' : '' !!}>
                                                    @if($paginator->currentPage() == $c)
                                                        <a href="javascript:void(0)">{{ $c }}</a>
                                                    @else
                                                        <a href="{{ $cp->url($paginator->url($c), $c) }}"
                                                            data-page="{{ $c }}">{{ $c }}</a>
                                                    @endif
                                                    </li>
                                            @endfor

                                                @if($paginator->currentPage() < $paginator->lastPage() - 2)
                                                    <li class="dots"><a href="javascript:void(0)">...</a></li>
                                                @endif

                                                <li{!! $paginator->currentPage() == $paginator->lastPage() ? ' class="current"' : '' !!}>
                                                    @if($paginator->currentPage() == $paginator->lastPage())
                                                        <a href="javascript:void(0)">{{ $paginator->lastPage() }}</a>
                                                    @else
                                                        <a href="{{ $cp->url($paginator->url($paginator->lastPage()), $paginator->lastPage()) }}"
                                                            data-page="{{ $paginator->lastPage() }}">{{ $paginator->lastPage() }}</a>
                                                    @endif
                                                    </li>
                                    @endif
            </ul>
            <div class="pagination-arrows">
                @if($paginator->currentPage() > 1)
                    <a href="{{ $cp->url($paginator->url($paginator->currentPage() - 1), $paginator->currentPage() - 1) }}"
                        data-page="{{ $paginator->currentPage() - 1 }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M27.9998 44.3333L11.6665 28L27.9998 11.6666" stroke="#0B0B0B" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M44.3332 28H11.6665" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </a>
                @else
                    <span class="disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M27.9998 44.3333L11.6665 28L27.9998 11.6666" stroke="#0B0B0B" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M44.3332 28H11.6665" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                @endif
                @if($paginator->currentPage() < $paginator->lastPage())
                    <a href="{{ $cp->url($paginator->url($paginator->currentPage() + 1), $paginator->currentPage() + 1) }}"
                        data-page="{{ $paginator->currentPage() + 1 }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M28.0002 11.6667L44.3335 28L28.0002 44.3334" stroke="#0B0B0B" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M11.6668 28L44.3335 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </a>
                @else
                    <span class="disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M28.0002 11.6667L44.3335 28L28.0002 44.3334" stroke="#0B0B0B" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M11.6668 28L44.3335 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                @endif
            </div>
    </div>
@endif