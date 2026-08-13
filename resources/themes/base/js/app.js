import $ from 'jquery';
import * as bootstrap from 'bootstrap';

window.$ = window.jQuery = $;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
});

// Бизнес-логика магазина (корзина/избранное/сравнение/фильтры каталога/чекаут) — вынесена в
// js/shop/*.js, см. docs/dynamic-page-import-plan.md, п.0.1. Презентационная логика самой темы
// `base` (FAQ-таб/форма отзыва/универсальные AJAX-формы/слайдер рекомендаций, ни от чего не
// зависящая специфика donor-дизайна) — в js/site.js по той же причине: страницы, унаследованные
// из `base` темой, собранной оркестратором Modules\ThemeImport, не переопределяют их разметку,
// поэтому она должна работать независимо от того, чей это app.js — донора или собственный.
require('./shop');
require('./site');