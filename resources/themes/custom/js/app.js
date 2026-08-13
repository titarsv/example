import $ from 'jquery';
import * as bootstrap from 'bootstrap';

window.$ = window.jQuery = $;

require('slick-carousel/slick/slick.js');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
});

// Корзина: добавление/удаление/изменение количества через AJAX,
// обновление счётчика в шапке и содержимого офканваса корзины.
function updateCartUI(cart) {
    $('.js-cart-count').text(cart.count).toggleClass('d-none', !cart.count);
    $('.js-cart-body').html(cart.html);
}

$(document).on('click', '.js-add-to-cart', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const data = {
        action: 'add',
        product_id: $btn.data('id'),
        quantity: 1,
    };
    const $variation = $btn.closest('.js-product-card').find('[name="variation"]:checked');
    if ($variation.length) {
        data.variation = $variation.val();
    }

    $.post('/cart/update', data, function (cart) {
        updateCartUI(cart);
        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas'));
        offcanvas.show();
    });
});

$(document).on('click', '.js-cart-remove', function (e) {
    e.preventDefault();
    $.post('/cart/update', { action: 'remove', product_id: $(this).data('id') }, updateCartUI);
});

$(document).on('change', '.js-cart-qty', function () {
    $.post('/cart/update', {
        action: 'update',
        product_id: $(this).data('id'),
        quantity: $(this).val(),
    }, updateCartUI);
});

// Избранное: переключение сердечка на карточке товара/странице товара.
$(document).on('click', '.js-wishlist-toggle', function (e) {
    e.preventDefault();
    const $btn = $(this);
    $.post('/wishlist/toggle', { product_id: $btn.data('id') }, function (response) {
        $btn.find('.bi').toggleClass('bi-heart bi-heart-fill', false);
        $btn.find('.bi').removeClass('bi-heart bi-heart-fill').addClass(response.in_wish ? 'bi-heart-fill' : 'bi-heart');
        $btn.toggleClass('text-danger', response.in_wish);
    });
});

// Всплывающее уведомление — используется сравнением для ошибок
// несовместимости («нельзя сравнивать наушники с телефонами» и т.п.).
function showToast(message, variant) {
    variant = variant || 'danger';
    let $container = $('#js-toast-container');
    if (!$container.length) {
        $container = $('<div id="js-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080"></div>').appendTo('body');
    }

    const $toast = $(
        '<div class="toast align-items-center text-bg-' + variant + ' border-0" role="alert">' +
            '<div class="d-flex">' +
                '<div class="toast-body"></div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>' +
            '</div>' +
        '</div>'
    );
    $toast.find('.toast-body').text(message);
    $container.append($toast);

    const toast = new bootstrap.Toast($toast[0], { delay: 5000 });
    $toast.on('hidden.bs.toast', function () {
        $toast.remove();
    });
    toast.show();
}

// Сравнение товаров — переключатель на карточке/странице товара. Список
// сравнения ничем не ограничен на добавление (любой товар можно положить,
// как в корзину); группировка по типу товара показывается только в шапке
// и на странице /compare — см. Modules/Compare.
function renderCompareGroups(groups) {
    if (!groups.length) {
        return '<li class="px-3 py-1 text-muted small">Список сравнения пуст</li>';
    }

    return groups.map(function (group) {
        return '<li class="d-flex align-items-center justify-content-between px-3 py-1 gap-2">' +
            '<a href="/compare?category=' + group.id + '" class="text-body text-decoration-none text-truncate">' +
                escapeHtml(group.name) + ' <span class="text-muted">(' + group.count + ')</span>' +
            '</a>' +
            '<button type="button" class="btn btn-sm btn-link text-danger p-0 js-compare-clear" data-category="' + group.id + '" aria-label="Удалить группу">' +
                '<i class="bi bi-trash"></i>' +
            '</button>' +
        '</li>';
    }).join('');
}

$(document).on('click', '.js-compare-toggle', function (e) {
    e.preventDefault();
    const productId = $(this).data('id');

    $.post('/compare/toggle', { product_id: productId })
        .done(function (response) {
            const $buttons = $('.js-compare-toggle[data-id="' + productId + '"]');
            $buttons.toggleClass('text-primary', response.in_compare);
            $buttons.find('.js-compare-label').text(response.in_compare ? 'В сравнении' : 'Сравнить');

            $('.js-compare-count').text(response.count).toggleClass('d-none', !response.count);
            $('.js-compare-dropdown').html(renderCompareGroups(response.groups));

            if (!response.in_compare && location.pathname.replace(/\/$/, '') === '/compare') {
                location.reload();
            }
        })
        .fail(function (xhr) {
            const message = (xhr.responseJSON && xhr.responseJSON.message) || 'Не удалось обновить список сравнения.';
            showToast(message);
        });
});

$(document).on('click', '.js-compare-clear', function (e) {
    e.preventDefault();
    const categoryId = $(this).data('category');
    $.post('/compare/clear', { category_id: categoryId }, function () {
        location.reload();
    });
});

// Страница сравнения (/compare) — закреплённая шапка таблицы при прокрутке,
// «только отличия», добавление товара прямо со страницы, «поделиться»,
// полноэкранный режим, перестановка колонок местами.
function updateCompareStickyOffset() {
    const $sticky = $('.js-compare-sticky');
    if (!$sticky.length) {
        return;
    }

    const $nav = $('nav.navbar');
    const offset = $nav.length ? $nav.outerHeight() : 0;
    $sticky.css('top', offset + 'px');
}

$(updateCompareStickyOffset);
$(window).on('load resize', updateCompareStickyOffset);

// Шапка и тело таблицы сравнения — два независимых overflow-x:auto блока
// (см. комментарий в compare.blade.php, почему они не могут быть одним
// общим скролл-контейнером), поэтому их горизонтальную прокрутку синхронизируем
// вручную. Флаг syncingCompareScroll — чтобы взаимные .scrollLeft-обновления
// не зациклились друг на друге.
let syncingCompareScroll = false;

function syncCompareScroll(source, target) {
    if (syncingCompareScroll) {
        return;
    }
    syncingCompareScroll = true;
    target.scrollLeft = source.scrollLeft;
    syncingCompareScroll = false;
}

$(document).on('scroll', '.js-compare-sticky', function () {
    const $body = $(this).closest('.js-compare-wrapper').find('.js-compare-scroll');
    if ($body.length) {
        syncCompareScroll(this, $body[0]);
    }
});

$(document).on('scroll', '.js-compare-scroll', function () {
    const $header = $(this).closest('.js-compare-wrapper').find('.js-compare-sticky');
    if ($header.length) {
        syncCompareScroll(this, $header[0]);
    }
});

$(document).on('change', '.js-compare-diff-only', function () {
    $('.js-compare-grid').toggleClass('js-diff-only-active', this.checked);
});

// "Поделиться страницей" — повторяет меню со страницы сравнения elmir.ua:
// мессенджеры открывают обычные share-ссылки; "Скопировать таблицу"/"для ИИ"
// ничего не запрашивают с сервера — собирают уже отрендеренную таблицу прямо
// из DOM (та же разметка, что видит пользователь).
//
// Сама ссылка — НЕ просто location.href: список сравнения хранится в сессии,
// у неё нет своего URL, поэтому обычный location.href по этой ссылке показал
// бы получателю его СОБСТВЕННЫЙ (чужой или пустой) список для этой категории,
// а не тот набор товаров, что видел отправитель. Поэтому в ссылку зашиваются
// id именно тех товаров, что сейчас в таблице (?ids=1,2,3) — при переходе
// сервер (CompareController::indexAction) сам разложит их по сессии
// получателя и покажет ровно этот набор.
function compareShareUrl() {
    const ids = $('.js-compare-header-cell[data-id]').map(function () {
        return $(this).data('id');
    }).get();

    if (!ids.length) {
        return location.href;
    }

    return location.origin + location.pathname + '?ids=' + ids.join(',');
}

function compareShareTitle() {
    const categoryName = $('.js-compare-wrapper').data('category-name') || '';
    return 'Сравнение товаров' + (categoryName ? ' | ' + categoryName : '');
}

$(document).on('click', '.js-compare-share-link', function (e) {
    e.preventDefault();

    const url = encodeURIComponent(compareShareUrl());
    const text = encodeURIComponent(compareShareTitle());
    const network = $(this).data('network');
    const targets = {
        telegram: 'https://t.me/share/url?url=' + url + '&text=' + text,
        whatsapp: 'https://wa.me/?text=' + text + '%20' + url,
        facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + url,
    };

    if (targets[network]) {
        window.open(targets[network], '_blank', 'noopener');
    }
});

function copyToClipboard(text, onDone) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(onDone, function () {
            fallbackCopy(text);
            onDone();
        });
    } else {
        fallbackCopy(text);
        onDone();
    }
}

function fallbackCopy(text) {
    const $ta = $('<textarea readonly></textarea>').css({ position: 'fixed', left: '-9999px', top: '0' }).val(text).appendTo('body');
    $ta[0].select();
    try {
        document.execCommand('copy');
    } catch (e) {
        // no-op: буфер обмена недоступен, копирование просто не произойдёт
    }
    $ta.remove();
}

$(document).on('click', '.js-compare-copy-link', function (e) {
    e.preventDefault();
    copyToClipboard(compareShareUrl(), function () {
        showToast('Ссылка скопирована', 'success');
    });
});

// Собирает уже отрендеренную таблицу сравнения в массив строк: первая —
// заголовок с названиями товаров, дальше — по одной строке на характеристику
// (в т.ч. «Наличие» и «Код товара»), как есть в DOM, вне зависимости от
// текущего состояния чекбокса «Только отличия» — сравнение копируется целиком.
function buildCompareRows() {
    const $headerCells = $('.js-compare-header-cell');
    const productCount = $headerCells.length;
    const header = [''];
    $headerCells.each(function () {
        header.push($(this).find('.small.fw-normal').text().trim());
    });

    const rows = [header];
    const children = $('.js-compare-grid').children().toArray();
    for (let i = 0; i + productCount < children.length; i += 1 + productCount) {
        const $label = $(children[i]);
        if (!$label.hasClass('js-compare-label') || !$label.text().trim()) {
            break;
        }
        const row = [$label.text().trim()];
        for (let j = 1; j <= productCount; j++) {
            row.push($(children[i + j]).text().trim());
        }
        rows.push(row);
    }

    return rows;
}

function buildCompareTsv(rows) {
    return rows.map(function (row) {
        return row.map(function (cell) {
            return String(cell).replace(/[\t\r\n]+/g, ' ');
        }).join('\t');
    }).join('\n');
}

function buildCompareHtmlTable(rows) {
    let html = '<table border="1" cellspacing="0" cellpadding="4">';
    rows.forEach(function (row, i) {
        const tag = i === 0 ? 'th' : 'td';
        html += '<tr>' + row.map(function (cell) {
            return '<' + tag + '>' + escapeHtml(cell) + '</' + tag + '>';
        }).join('') + '</tr>';
    });
    return html + '</table>';
}

$(document).on('click', '.js-compare-copy-table', function (e) {
    e.preventDefault();
    const rows = buildCompareRows();
    const tsv = buildCompareTsv(rows);

    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        const html = buildCompareHtmlTable(rows);
        navigator.clipboard.write([
            new ClipboardItem({
                'text/html': new Blob([html], { type: 'text/html' }),
                'text/plain': new Blob([tsv], { type: 'text/plain' }),
            }),
        ]).then(function () {
            showToast('Таблица скопирована', 'success');
        }, function () {
            fallbackCopy(tsv);
            showToast('Таблица скопирована', 'success');
        });
    } else {
        fallbackCopy(tsv);
        showToast('Таблица скопирована', 'success');
    }
});

// «Скопировать для ИИ» — та же таблица, но в виде JSON, удобного для
// вставки в чат с ИИ: список товаров с характеристиками плюс явный список
// пунктов, по которым товары различаются (row.differs уже посчитан на
// сервере и отмечен классом .js-row-differs — здесь просто читаем его).
function buildCompareAiData() {
    const categoryName = $('.js-compare-wrapper').data('category-name') || '';
    const $headerCells = $('.js-compare-header-cell');
    const items = $headerCells.map(function () {
        const $cell = $(this);
        return {
            name: $cell.find('.small.fw-normal').text().trim(),
            url: $cell.data('url') || '',
            price: $cell.data('price') || null,
            specs: {},
        };
    }).get();

    const differing_specs = [];
    const productCount = $headerCells.length;
    const children = $('.js-compare-grid').children().toArray();
    for (let i = 0; i + productCount < children.length; i += 1 + productCount) {
        const $label = $(children[i]);
        const name = $label.text().trim();
        if (!$label.hasClass('js-compare-label') || !name) {
            break;
        }
        if ($label.hasClass('js-row-differs')) {
            differing_specs.push(name);
        }
        for (let j = 1; j <= productCount; j++) {
            items[j - 1].specs[name] = $(children[i + j]).text().trim();
        }
    }

    return { category: categoryName, differing_specs: differing_specs, items: items };
}

$(document).on('click', '.js-compare-copy-ai', function (e) {
    e.preventDefault();
    const json = JSON.stringify(buildCompareAiData(), null, 2);
    copyToClipboard(json, function () {
        showToast('Данные для ИИ скопированы', 'success');
    });
});

$(document).on('click', '.js-compare-fullscreen', function () {
    const el = document.querySelector('.js-compare-wrapper');
    if (!el) {
        return;
    }

    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else if (el.requestFullscreen) {
        el.requestFullscreen().catch(function () {});
    }
});

// Порядок колонок пересчитываем в JS-массиве (не трогая DOM) и сразу
// перезагружаем страницу с уже сохранённым порядком.
$(document).on('click', '.js-compare-move-left, .js-compare-move-right', function () {
    const $btn = $(this);
    const $wrapper = $btn.closest('.js-compare-wrapper');
    const index = $btn.closest('.js-compare-header-cell').data('index');
    const targetIndex = $btn.hasClass('js-compare-move-left') ? index - 1 : index + 1;

    const ids = $wrapper.find('.js-compare-header-cell[data-id]').map(function () {
        return $(this).data('id');
    }).get();

    if (targetIndex < 0 || targetIndex >= ids.length) {
        return;
    }

    const tmp = ids[index];
    ids[index] = ids[targetIndex];
    ids[targetIndex] = tmp;

    $.post('/compare/reorder', { category_id: $wrapper.data('category'), ids: ids }, function () {
        location.reload();
    });
});

// Вариации товара (объём/размер и т.п.) — переключение цены.
$(document).on('click', '.js_variation:not(.disabled)', function () {
    const $this = $(this);
    const $card = $this.closest('.js-product-card');
    const id = $this.data('id');

    $card.find('.js_variation').removeClass('js_active current');
    $this.addClass('js_active current');

    const $input = $card.find('.js_var_' + id);
    $card.find('[name="variation"]').prop('checked', false);

    if ($input.length) {
        $input.prop('checked', true);
        const price = $input.data('price');
        const originalPrice = $input.data('original_price');
        $card.find('.js_current_price').text('₽' + price);
        $card.find('.js_old_price').remove();
        if (originalPrice > price) {
            $card.find('.js_current_price').after('<span class="text-muted text-decoration-line-through js_old_price ms-1">₽' + originalPrice + '</span>');
        }
    }
});

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

// Фильтр FAQ по категориям.
$(document).on('click', '.js-faq-tab', function () {
    const tab = $(this).data('tab');
    $('.js-faq-tab').removeClass('btn-primary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('btn-primary');

    $('.js-faq-item').each(function () {
        $(this).toggle(tab === 'all' || $(this).data('tab') == tab);
    });
});

// Отслеживание заказа по номеру и email.
$(document).on('submit', '#js_track_order', function (e) {
    e.preventDefault();
    const $form = $(this);
    $form.find('.js-track-error').addClass('d-none');

    $.post('/track_order', $form.serialize(), function (response) {
        if (response.success) {
            $('.js-track-result').html(response.html);
        } else {
            $form.find('.js-track-error').text(response.message).removeClass('d-none');
        }
    });
});

// Форма отзыва о товаре/о магазине.
$(document).on('submit', '.js-review-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.post($form.attr('action'), $form.serialize())
        .done(function (response) {
            if (response && response.error) {
                $form.find('.js-review-error').text('Проверьте, все ли обязательные поля заполнены.').removeClass('d-none');
                return;
            }
            $form.closest('.modal').find('.modal-body').html('<div class="alert alert-success mb-0">Спасибо! Ваш отзыв отправлен на модерацию.</div>');
        })
        .fail(function () {
            $form.find('.js-review-error').text('Ошибка отправки, попробуйте ещё раз.').removeClass('d-none');
        });
});

$(document).on('click', '.js-rating-input .bi', function () {
    const $wrapper = $(this).closest('.js-rating-input');
    const value = $(this).data('value');
    $wrapper.find('input[name="grade"]').val(value);
    $wrapper.find('.bi').each(function () {
        $(this).toggleClass('bi-star-fill', $(this).data('value') <= value).toggleClass('bi-star', $(this).data('value') > value);
    });
});

// Изменение количества/удаление товара на странице оформления заказа —
// проще всего пересчитать перезагрузкой страницы после ответа сервера.
$(document).on('change', '.js_checkout_qty', function () {
    $.post('/cart/update', { action: 'update', product_id: $(this).data('prod-id'), quantity: $(this).val() }, function () {
        location.reload();
    });
});

$(document).on('click', '.js_remove_product_from_checkout', function () {
    $.post('/cart/update', { action: 'remove', product_id: $(this).data('id') }, function () {
        location.reload();
    });
});

// Оформление заказа.
$(document).on('submit', '#order-checkout', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('.js_checkout_submit');
    $form.find('.js-checkout-error').addClass('d-none');
    $btn.prop('disabled', true);

    $.post(location.pathname + location.search, $form.serialize())
        .done(function (response) {
            if (response.success === 'redirect') {
                location.href = response.url || ('/thanks?order_id=' + response.order_id);
                return;
            }
            if (response.error) {
                const messages = typeof response.error === 'string' ? response.error : Object.values(response.error).join(' ');
                $form.find('.js-checkout-error').text(messages).removeClass('d-none');
                $btn.prop('disabled', false);
            }
        })
        .fail(function () {
            $form.find('.js-checkout-error').text('Ошибка оформления заказа, попробуйте ещё раз.').removeClass('d-none');
            $btn.prop('disabled', false);
        });
});

// Купон на странице корзины/оформления заказа.
$(document).on('submit', '.js-coupon-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.post('/apply_coupon', $form.serialize(), function (response) {
        if (response.result === 'success') {
            location.reload();
        } else {
            $form.find('.js-coupon-error').text(response.msg).removeClass('d-none');
        }
    });
});

// Универсальная отправка форм (подписка в футере, контакты, отзывы)
// без перезагрузки страницы — с простым инлайн-уведомлением.
$(document).on('submit', '.ajax_form', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('[type="submit"]');
    const originalText = $btn.text();

    $form.find('.js-form-alert').remove();
    $btn.prop('disabled', true);

    $.post($form.attr('action'), $form.serialize())
        .done(function () {
            $form.prepend(
                '<div class="alert alert-success js-form-alert">' +
                ($form.data('success-title') || 'Готово') +
                ($form.data('success-message') ? ' ' + $form.data('success-message') : '') +
                '</div>'
            );
            $form[0].reset();
        })
        .fail(function () {
            $form.prepend(
                '<div class="alert alert-danger js-form-alert">' +
                ($form.data('error-title') || 'Ошибка отправки, попробуйте ещё раз.') +
                '</div>'
            );
        })
        .always(function () {
            $btn.prop('disabled', false).text(originalText);
        });
});

// Живой поиск в шапке.
function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
}

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

// Слайдеры блоков с товарными рекомендациями (похожие/сопутствующие/
// рекомендуемые/хиты продаж и т.п.) — везде одна и та же карусель.
$('.js-products-slider').slick({
    rows: 0, // иначе slick сам оборачивает каждый слайд доп. div'ом с
             // инлайновым display:inline-block, и height:100% до карточки не доходит
    slidesToShow: 5,
    slidesToScroll: 1,
    infinite: false,
    prevArrow: '<button type="button" class="slick-prev"><i class="bi bi-chevron-left"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="bi bi-chevron-right"></i></button>',
    responsive: [
        { breakpoint: 992, settings: { slidesToShow: 3 } },
        { breakpoint: 576, settings: { slidesToShow: 2 } },
    ],
});
