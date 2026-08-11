# Storefront theme system

The storefront (everything under `public.*` view names — catalog, product,
cart, checkout, account, blog, static pages, etc.) is not hardcoded to a
single set of views. It lives in a **theme**, and the app can run any theme
that's present on disk.

`base` is the reference theme: a deliberately plain-designed but functionally
complete implementation of the storefront (catalog with filters, product
page, cart/checkout with delivery+payment+coupons, account/orders, wishlist,
blog, reviews, static content pages...). Project-specific themes are meant to
be forked from it and then given their own design — they only need to
override the files that actually differ; everything else falls back to
`base` automatically.

## Directory convention

```
resources/themes/{theme}/
    views/    # Blade views — same internal layout as the old resources/views/public
    scss/     # entry: scss/app.scss
    js/       # entry: js/app.js
    images/   # static, copied as-is
    fonts/    # static, copied as-is
```

Compiled output for a theme goes to `public/themes/{theme}/...` (versioned
via Laravel Mix's manifest).

## Switching the active theme

`config/theme.php`:

```php
'active' => env('ACTIVE_THEME', 'base'),
'fallback' => 'base',
```

1. Set `ACTIVE_THEME=your-theme` in `.env`.
2. Add `'your-theme'` to the `available` list in `config/theme.php` (informational, for tooling/validation).
3. `php artisan config:clear` (view path resolution is computed in `config/view.php` itself, at config-load time — see "Why not a ServiceProvider" below).
4. `npm run dev` (or `npm run prod`) — `webpack.mix.js` builds whatever `ACTIVE_THEME` points to (it reads the same `.env` value) into `public/themes/{theme}`.

## How view resolution works

`config/view.php` prepends the active theme's `views` directory (and the
fallback theme's, if different) to Laravel's view search paths, before the
default `resources/views`. Laravel's view finder checks each path in order
and returns the first match — so controllers keep calling plain
`view('public.catalog')` unchanged, and a project theme only ships the files
it actually overrides:

```
resources/themes/my-shop/views/public/product.blade.php   # overridden
                                                            # everything else
                                                            # falls back to:
resources/themes/base/views/public/*.blade.php
```

`resources/views/{admin,auth,emails,errors,layouts}` are **not** themed —
admin runs on a separate template (Vuexy) and `errors/*` must resolve even
before the app has fully booted.

### Why this lives in `config/view.php`, not a ServiceProvider

The obvious place to compute `view.paths` looks like a `ServiceProvider::register()`.
That doesn't work here: `webwizo/laravel-shortcodes` eagerly resolves and
rebinds Laravel's `view` factory during its *own* `register()`, which runs
before any `App\Providers\*` provider gets a chance — by the time an app
provider's `register()` runs, the view finder has already been constructed
(with the un-themed paths) and cached. Config files are evaluated (via
`env()`) before any provider registers at all, so `config/view.php` is the
only point that's reliably early enough.

## Blade/asset helpers (`app/Helpers/helpers.php`)

- `theme_mix($path)` — versioned URL for a compiled asset, e.g. `theme_mix('css/app.css')`.
- `theme_asset($path)` — URL for a static asset copied as-is, e.g. `theme_asset('images/favicon.png')`.
- `theme_path($path)` — absolute filesystem path into the active theme's source (rarely needed directly).
- `theme_relative_path($path)` — same, but relative to `base_path()`; used wherever code goes through the `local` filesystem disk (which is rooted at `base_path()`, see `config/filesystems.php`) — e.g. the admin page/block template editor (`PagesController`, `BlocksController`) reads and writes theme blade files this way, so edits made there land in whichever theme is currently active.

Always use these instead of Laravel's plain `mix()`/`asset()` for anything
under a theme's `views`/`scss`/`js`/`images`/`fonts`.

## Creating a new theme

```
php artisan theme:make my-shop --from=base
```

Copies `resources/themes/base` to `resources/themes/my-shop`. From there:

1. Delete whatever you don't need to change — deleted files fall back to `base`.
2. Point `.env`'s `ACTIVE_THEME` at it and add it to `config/theme.php`'s `available` list.
3. Redesign `scss/_variables.scss` first (colors, fonts, spacing) — most of the
   base theme's layout is written against those variables rather than
   hardcoded values, so a lot of visual identity comes from that file alone.
4. `npm run dev` to build its assets.

## Known caveats in the `base` theme (as of the initial infra migration)

The CSS build was collapsed from the old multi-bundle "critical CSS" pipeline
(`laravel-mix-criticalcss-pro`, per-page critical CSS extraction via headless
Chrome) down to a single `scss/app.scss` → `css/app.css` bundle, for
simplicity. A theme is free to reintroduce a more sophisticated build if it
needs the performance win; nothing in the resolution mechanism above depends
on there being exactly one CSS/JS entry point.
