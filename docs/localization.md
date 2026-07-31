# JavaScript Localization System

This document provides a comprehensive guide to the JavaScript localization system used in the admin panel.

## Overview
The localization system allows for easy translation of user-facing strings in JavaScript files. It provides a simple API for translating strings, handling pluralization, and replacing placeholders.

## Setup

The localization system consists of two main components:

1. **PHP Translation Files**: Located in `resources/lang/en/js_messages.php`
2. **JavaScript Helper**: Located in `public/js/admin/localization.js`

## Usage

### Basic Translation

Use the `__()` function to translate a string:

```javascript
// Example: Basic translation
const message = __('Hello World');
```

### With Replacements

You can use placeholders in your translation strings and replace them with dynamic values:

```javascript
// In your PHP translation file:
// 'welcome' => 'Welcome, :name!',

// In your JavaScript:
const welcomeMessage = __('welcome', { name: 'John' });
// Result: "Welcome, John!"
```

### Pluralization

Use the `__n()` function for pluralization:

```javascript
// In your PHP translation file:
// 'apple' => 'apple',
// 'apples' => 'apples',

// In your JavaScript:
const count = 5;
const message = __n('apple', 'apples', count);
// Result: "5 apples"
```

## Adding New Translations

1. **Add translations to the PHP file**:
   Open `resources/lang/en/js_messages.php` and add your new translations:

   ```php
   return [
       // ... existing translations ...
       'new_key' => 'New translation',
   ];
   ```

2. **Use the translation in JavaScript**:
   ```javascript
   const message = __('new_key');
   ```

## Best Practices

1. **Use descriptive keys**: Use dot notation for better organization (e.g., 'products.delete_confirmation').
2. **Keep translations in context**: Group related translations together in the PHP file.
3. **Use placeholders for dynamic content**: This makes translations more flexible.
4. **Avoid string concatenation**: Instead of `__('Showing') + ' ' + count + ' ' + __('items')`, use `__('showing_items', { count: count })`.

## Available Functions

### `__(key, replace = {})`
- `key` (string): The translation key
- `replace` (object): Key-value pairs for placeholder replacement
- Returns: The translated string

### `__n(singular, plural, count, replace = {})`
- `singular` (string): The singular form translation key
- `plural` (string): The plural form translation key
- `count` (number): The count to determine singular/plural
- `replace` (object): Key-value pairs for placeholder replacement
- Returns: The translated string with count and replacements applied

## Integration with UI Libraries

### Toastr Notifications

```javascript
toastr.success(__('Data saved successfully'));
```

### SweetAlert Dialogs

```javascript
Swal.fire({
    title: __('Confirm'),
    text: __('Are you sure you want to delete this item?'),
    confirmButtonText: __('Yes, delete it!'),
    cancelButtonText: __('Cancel')
});
```

### DataTables

```javascript
$('#example').DataTable({
    language: {
        search: __('Search'),
        lengthMenu: __('Show _MENU_ entries'),
        info: __('Showing _START_ to _END_ of _TOTAL_ entries'),
        // ... other DataTable translations
    }
});
```

## Common Pitfalls

1. **Missing Translations**: If a translation key is missing, the key itself will be returned. Always check the console for warnings about missing translations.

2. **Dynamic Keys**: Avoid generating translation keys dynamically as this can make it hard to track all translations.

3. **HTML in Translations**: Be cautious with HTML in translations. If needed, use triple brackets in your templates to render HTML: `{!! __('key_with_html') !!}`.

## Testing

To test your translations:

1. Change your application locale in the admin panel.
2. Verify all user-facing strings are properly translated.
3. Check the browser console for any missing translation warnings.

## Adding Support for New Languages

1. Create a new directory in `resources/lang/` (e.g., `fr` for French).
2. Copy the contents of `resources/lang/en/js_messages.php` to the new directory.
3. Translate all the strings in the new file.
4. Update the language switcher in the admin panel to include the new language.

## Troubleshooting

- **Translations not updating?** Clear your browser cache and Laravel cache with `php artisan cache:clear`.
- **Missing translations?** Check the browser console for warnings about missing translation keys.
- **Incorrect translations?** Verify the translation key exists in the correct language file and the locale is set correctly.

## Examples

### Basic Example

```javascript
// In your PHP translation file:
// 'welcome' => 'Welcome, :name!',

// In your JavaScript:
const name = 'John';
const welcomeMessage = __('welcome', { name: name });
console.log(welcomeMessage); // Output: "Welcome, John!"
```

### Pluralization Example

```javascript
// In your PHP translation file:
// 'apple' => 'apple',
// 'apples' => 'apples',
// 'cart_count' => 'You have :count :item in your cart',

// In your JavaScript:
const count = 5;
const item = __n('apple', 'apples', count);
const message = __('cart_count', { 
    count: count, 
    item: item 
});
// Output: "You have 5 apples in your cart"
```

## Conclusion

This localization system provides a robust way to handle translations in the admin panel's JavaScript code. By following these guidelines, you can ensure a consistent and maintainable approach to internationalization.
