/**
 * Localization helper for admin panel
 * 
 * Setup:
 * 1. In your blade template, add: 
 *    <script>
 *        window.translations = @json(__('messages'));
 *    </script>
 * 2. Include this file after jQuery and before any other scripts
 * 
 * Usage:
 * 1. Basic: __('key.to.translate')
 * 2. With replacements: __('welcome.message', {name: 'John'})
 * 3. Pluralization: __n('item', 'items', 5)
 */

// Main translation function
window.__ = function (key, replace = {}) {
    // If no translations loaded, return key
    if (!window.translations) {
        console.warn('Translations not loaded. Make sure to set window.translations in your blade template.');
        return key;
    }
    
    let translation = window.translations[key] || key;
    
    // Replace placeholders if any
    if (typeof translation === 'string') {
        for (let placeholder in replace) {
            if (replace.hasOwnProperty(placeholder)) {
                translation = translation.replace(new RegExp(`:${placeholder}`, 'g'), replace[placeholder]);
            }
        }
    }
    
    return translation;
};

// Pluralization helper
window.__n = function (singular, plural, count, replace = {}) {
    const key = count === 1 ? singular : plural;
    const translation = __(key, replace);
    return translation.replace(':count', count);
};

// Initialize empty translations object if not defined
window.translations = window.translations || {};

// Datatables localization
if (typeof $.fn.dataTable !== 'undefined') {
    window.localization = window.localization || {};
    window.localization.datatable = {
        "processing": __("Processing..."),
        "search": __("Search"),
        "lengthMenu": __("Show _MENU_ entries"),
        "info": __("Showing _START_ to _END_ of _TOTAL_ entries"),
        "infoEmpty": __("Showing 0 to 0 of 0 entries"),
        "infoFiltered": __("(filtered from _MAX_ total entries)"),
        "infoPostFix": "",
        "loadingRecords": __("Loading..."),
        "zeroRecords": __("No matching records found"),
        "emptyTable": __("No data available in table"),
        "paginate": {
            "first": __("First"),
            "previous": __("Previous"),
            "next": __("Next"),
            "last": __("Last")
        },
        "aria": {
            "sortAscending": ": " + __("activate to sort column ascending"),
            "sortDescending": ": " + __("activate to sort column descending")
        }
    };
}
