const mix = require('laravel-mix');

require('laravel-mix-criticalcss-pro');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/scss/header.scss', 'public/css')
    .sass('resources/scss/section-1.scss', 'public/css/temp')
    .sass('resources/scss/critical.scss', 'public/css/temp')
    .sass('resources/scss/app.scss', 'public/css')
    .sass('resources/scss/invisible.scss', 'public/css')
    .criticalCssPro({
    enabled: mix.inProduction(),
    paths: {
        base: 'https://example.lh/',
        templates: './public/css/critical/',
        suffix: '.min'
    },
    urls: [
        { url: '', template: 'index', excludedSources: ['css/header.css', 'css/temp/section-1.css', 'css/invisible.css'] },
        { url: '', template: 'index_header', excludedSources: ['css/invisible.css'], height: 1400},
        { url: 'disposable-vape/effects-creative', template: 'catalog', excludedSources: ['css/header.css', 'css/temp/section-1.css', 'css/invisible.css'] },
        { url: 'disposable-vape/effects-creative', template: 'catalog_header', excludedSources: ['css/temp/section-1.css', 'css/app.css', 'css/invisible.css'], height: 1400 },
        { url: 'trips-ahoy-crunchy-cookies', template: 'product', excludedSources: ['css/header.css', 'css/temp/section-1.css', 'css/invisible.css'] },
        { url: 'trips-ahoy-crunchy-cookies', template: 'product_header', excludedSources: ['css/temp/section-1.css', 'css/app.css', 'css/invisible.css'], height: 1400 },
    ],
    options: {
        minify: true,
        width:1920,
        height:30000,
        penthouse: {
            forceInclude: [
                '.row',
                /\.col-.*/,
                /.*:hover.*/,
                /.*\.slick-.*/,
                /\.found-img/,
                /\.selectable/,
                /\.selectable a/,
                /\.selectable span/,
                '.collapsing',
                '.collapse.in'
            ],
            timeout:1200000
        }
    }
})
    .version()
    .before(stats => {
        // stats.manifest.add('fonts/Roboto-Regular.woff');
        // stats.manifest.add('fonts/Roboto-Medium.woff2');
    })
    .after(stats => {
        // console.log(Object.keys(stats.compilation.assets));
    });

mix.browserSync({
    proxy: 'https://example.lh',
    files: [
        'public/dev/*.html',
        'app/**/*.php',
        'resources/views/**/*.php',
        'public/js/**/*.js',
        'public/css/**/*.css',
        'resources/scss/**/*.scss'
    ]
});

if (mix.inProduction()){
    mix.version();
}
