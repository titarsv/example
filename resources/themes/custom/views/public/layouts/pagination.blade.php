@if($paginator->lastPage() > 1)
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 2;

        $pages = collect([1, $last]);
        for($p = $current - $window; $p <= $current + $window; $p++){
            if($p >= 1 && $p <= $last){
                $pages->push($p);
            }
        }
        $pages = $pages->unique()->sort()->values();
    @endphp
    <nav aria-label="Навигация по страницам">
        <ul class="pagination justify-content-center">
            <li class="page-item{{ $current <= 1 ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $current > 1 ? $cp->url($paginator->url($current - 1), $current - 1) : '#' }}" data-page="{{ $current - 1 }}">&laquo;</a>
            </li>
            @foreach($pages as $i => $p)
                @if($i > 0 && $p - $pages[$i - 1] > 1)
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                @endif
                <li class="page-item{{ $current == $p ? ' active' : '' }}">
                    <a class="page-link" href="{{ $cp->url($paginator->url($p), $p) }}" data-page="{{ $p }}">{{ $p }}</a>
                </li>
            @endforeach
            <li class="page-item{{ $current >= $last ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $current < $last ? $cp->url($paginator->url($current + 1), $current + 1) : '#' }}" data-page="{{ $current + 1 }}">&raquo;</a>
            </li>
        </ul>
    </nav>
@endif
