const mix = require('laravel-mix');
const fs = require('fs');
require('dotenv').config();

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Builds the *active* storefront theme only (resources/themes/{theme}),
 | matching config/theme.php's `active` (same ACTIVE_THEME env var). Output
 | goes to public/themes/{theme}/..., versioned and read back by the
 | theme_mix()/theme_asset() Blade helpers (app/Helpers/helpers.php). See
 | docs/themes.md for how themes are structured and switched.
 |
 */

const theme = process.env.ACTIVE_THEME || 'base';
const themeSrc = `resources/themes/${theme}`;
const themeDist = `public/themes/${theme}`;

mix.js(`${themeSrc}/js/app.js`, `${themeDist}/js`)
    .sass(`${themeSrc}/scss/app.scss`, `${themeDist}/css`);

if (fs.existsSync(`${themeSrc}/images`)) {
    mix.copyDirectory(`${themeSrc}/images`, `${themeDist}/images`);
}

if (fs.existsSync(`${themeSrc}/fonts`)) {
    mix.copyDirectory(`${themeSrc}/fonts`, `${themeDist}/fonts`);
}

mix.version();

mix.browserSync({
    proxy: 'https://example.lh',
    files: [
        'public/dev/*.html',
        'app/**/*.php',
        `${themeSrc}/views/**/*.php`,
        `${themeDist}/js/**/*.js`,
        `${themeDist}/css/**/*.css`,
        `${themeSrc}/scss/**/*.scss`
    ]
});
