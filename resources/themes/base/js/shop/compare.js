import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { escapeHtml } from './utils';

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