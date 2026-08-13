import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { escapeHtml } from './utils';

// Каталог: применение фильтров/сортировки без перезагрузки страницы.
function submitCatalogFilters($form) {
    // Пустые значения (например, невыбранный "Любой" в single_select) не
    // отправляем — иначе backend получит пустую строку в списке filters[].
    const data = $form.serializeArray().filter(function (field) {
        return field.value !== '';
    });

    $.post('/products/filter', $.param(data), function (response) {
        $('#catalogProducts').html(response.html);
        $('#catalogPagination').html(response.pagination);
        $('#catalogFilterFields').html(response.filters);
        $('#catalogSelectedFilters').html(response.checked);
        $('.js-products-count').text(response.count);
        if (response.link) {
            history.replaceState(null, '', response.link);
        }
    });
}

function submitCatalogFiltersFromStart() {
    $('#catalogFilters input[name="page"]').val(1);
    submitCatalogFilters($('#catalogFilters'));
}

// Атрибуты типа single_radio/yes_no/single_color_radio рисуются чекбоксами
// (не нативными radio — иначе одинаковый name="filters[]" сделал бы
// исключающими друг друга чекбоксы РАЗНЫХ атрибутов). Поэтому единственность
// выбора внутри группы обеспечиваем сами, до того как сработает общий
// обработчик отправки формы ниже.
$(document).on('change', '.js-filter-single', function () {
    if (this.checked) {
        $(this).closest('.accordion-body').find('.js-filter-single').not(this).prop('checked', false);
    }
});

$(document).on('change', '#catalogFilters input[type="checkbox"], #catalogFilters select[name="filters[]"]', submitCatalogFiltersFromStart);

$(document).on('submit', '#catalogFilters', function (e) {
    e.preventDefault();
    submitCatalogFiltersFromStart();
});

// Атрибуты типа range/range_slider: числовые поля/слайдер двигают не сам
// filters[] напрямую, а скрытые чекбоксы с конкретными value_id, чей
// числовой value попадает в выбранный диапазон — так на бэкенде не
// потребовалось заводить отдельный механизм фильтрации "от-до".
function applyAttrRange($wrapper) {
    let min = parseFloat($wrapper.find('.js-attr-range-min-input').val());
    let max = parseFloat($wrapper.find('.js-attr-range-max-input').val());

    if (min > max) {
        const tmp = min;
        min = max;
        max = tmp;
    }

    $wrapper.find('.js-attr-range-min-label').text(min + ($wrapper.data('unit') || ''));
    $wrapper.find('.js-attr-range-max-label').text(max + ($wrapper.data('unit') || ''));

    $wrapper.find('.js-attr-range-checkbox').each(function () {
        const value = parseFloat($(this).data('numeric'));
        $(this).prop('checked', value >= min && value <= max);
    });

    submitCatalogFiltersFromStart();
}

let attrRangeTimer;
$(document).on('input change', '.js-attr-range-min-input, .js-attr-range-max-input', function () {
    clearTimeout(attrRangeTimer);
    const $wrapper = $(this).closest('.js-attr-range');
    attrRangeTimer = setTimeout(function () {
        applyAttrRange($wrapper);
    }, 300);
});

$(document).on('click', '.js_remove_filter', function () {
    const id = $(this).data('id');
    const type = $(this).data('type');

    if (type === 'attribute') {
        const $form = $('#catalogFilters');
        $form.find('input[name="filters[]"][value="' + id + '"]').prop('checked', false);
        $form.find('select[name="filters[]"]').each(function () {
            if ($(this).val() == id) {
                $(this).val('');
            }
            $(this).find('option[value="' + id + '"]').prop('selected', false);
        });
    } else if (type === 'is_sale') {
        $('#catalogFilters').find('#isSale').prop('checked', false);
    }

    submitCatalogFiltersFromStart();
});

$(document).on('click', '.js_clear_filters', function () {
    $('#catalogFilters')[0].reset();
    submitCatalogFiltersFromStart();
});

$(document).on('click', '#catalogPagination .page-link', function (e) {
    if ($(this).closest('.page-item').hasClass('disabled')) {
        e.preventDefault();
        return;
    }
    e.preventDefault();
    $('#catalogFilters input[name="page"]').val($(this).data('page'));
    submitCatalogFilters($('#catalogFilters'));
    $('html, body').animate({ scrollTop: $('#catalogProducts').offset().top - 100 }, 300);
});

$(document).on('change', '.js-catalog-sort-select', function () {
    $('#catalogFilters input[name="order"]').val($(this).val());
    submitCatalogFiltersFromStart();
});

// Быстрый просмотр товара — подгружаем разметку карточки в модалку.
$(document).on('click', '.js-quick-view', function (e) {
    e.preventDefault();
    const id = $(this).data('id');
    const $modalBody = $('#quickViewModal .modal-body');
    $modalBody.html('<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickViewModal')).show();
    $.post('/product_popup', { id: id }, function (html) {
        $modalBody.html(html);
    });
});

// Живой поиск в шапке.
function renderSearchResults(products) {
    if (!products.length) {
        return '<span class="dropdown-item-text text-muted">Ничего не найдено</span>';
    }

    return products.map(function (product) {
        return '<a class="dropdown-item d-flex align-items-center gap-2" href="' + escapeHtml(product.url) + '">' +
            '<img src="' + escapeHtml(product.image) + '" alt="" width="40" height="40" class="object-fit-cover flex-shrink-0">' +
            '<span class="flex-grow-1 text-truncate">' + escapeHtml(product.name) + '</span>' +
            '<span class="text-nowrap fw-semibold">' + escapeHtml(product.price) + '</span>' +
            '</a>';
    }).join('');
}

let searchTimer;
$(document).on('input', '.js-live-search', function () {
    const $input = $(this);
    const $results = $('.js-search-results');
    clearTimeout(searchTimer);
    const text = $input.val().trim();

    if (text.length < 2) {
        $results.removeClass('show').addClass('d-none').empty();
        return;
    }

    searchTimer = setTimeout(function () {
        $.get('/livesearch', { text }, function (products) {
            $results.html(renderSearchResults(products)).removeClass('d-none').addClass('show');
        });
    }, 300);
});

$(document).on('click', function (e) {
    if (!$(e.target).closest('.js-search-wrapper').length) {
        $('.js-search-results').removeClass('show').addClass('d-none');
    }
});