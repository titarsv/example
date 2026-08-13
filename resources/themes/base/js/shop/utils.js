import $ from 'jquery';

// Общий мелкий хелпер для js/shop/*.js — экранирование значений, вставляемых
// в разметку через строковую конкатенацию (карточки сравнения, живой поиск).
export function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
}