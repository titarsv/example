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
const fallbackSrc = 'resources/themes/base';

// js/app.js и scss/app.scss — как и Blade-вьюхи (config/view.php) и images/fonts (copyDirectory
// ниже) — не обязаны существовать в активной теме: тема, не переопределяющая ни JS, ни SCSS,
// вправе не таскать их устаревшую копию и наследовать от base. В отличие от вьюх, здесь нет
// готового движка резолва путей — путь к entry-файлу вычисляем сами, тем же принципом.
const jsEntry = fs.existsSync(`${themeSrc}/js/app.js`) ? `${themeSrc}/js/app.js` : `${fallbackSrc}/js/app.js`;
const sassEntry = fs.existsSync(`${themeSrc}/scss/app.scss`) ? `${themeSrc}/scss/app.scss` : `${fallbackSrc}/scss/app.scss`;

mix.js(jsEntry, `${themeDist}/js`)
    .sass(sassEntry, `${themeDist}/css`);

if (fs.existsSync(`${themeSrc}/images`)) {
    mix.copyDirectory(`${themeSrc}/images`, `${themeDist}/images`);
}

if (fs.existsSync(`${themeSrc}/fonts`)) {
    mix.copyDirectory(`${themeSrc}/fonts`, `${themeDist}/fonts`);
}

// scss/imported/{page}/entry.scss — per-page изолированные entry-точки, которые раскладывает
// Modules\ThemeImport (см. docs/dynamic-page-import-plan.md, «Источник архива»): SCSS-переменные
// донора конкретной страницы не должны утекать в общий app.scss темы (реальная коллизия имён,
// не гипотетическая — у донора и темы могут совпадать имена вроде $dark), поэтому каждая такая
// страница компилируется отдельным bundle'ом и подключается своим <link> только на своей странице.
const importedScssRoot = `${themeSrc}/scss/imported`;

if (fs.existsSync(importedScssRoot)) {
    for (const page of fs.readdirSync(importedScssRoot)) {
        const entry = `${importedScssRoot}/${page}/entry.scss`;

        if (fs.existsSync(entry)) {
            mix.sass(entry, `${themeDist}/css/imported/${page}.css`);
        }
    }
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
