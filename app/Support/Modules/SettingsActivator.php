<?php

namespace App\Support\Modules;

use App\Models\Setting;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

/**
 * Stores module on/off state in the existing `settings` table (key `modules_settings`,
 * one JSON blob keyed by lowercase module slug) instead of nwidart's default
 * `modules_statuses.json` file, so the same admin "Модули" settings screen and the
 * same `module_active()` helper work for real nwidart modules and for in-core-only
 * toggles (e.g. cart_checkout) that never become an actual Modules/ package.
 *
 * hasStatus()/flags() get called during ServiceProvider::register() for every
 * request - including by Nwidart\Modules\ModuleManifest, which decides right
 * there whether a module's provider (and therefore its routes/views) gets
 * registered at all. At that point in the bootstrap, Eloquent's connection
 * resolver is not reliably wired up yet (confirmed: a DB-backed read here
 * silently fails and falls back to "enabled" mid-request, not just in
 * artisan's package:discover). So the actual source of truth for reads is a
 * small file cache under bootstrap/cache/ (already gitignored, same spot
 * Laravel/nwidart keep their own bootstrap caches) that mirrors the
 * `settings` row - safe to read at any bootstrap stage, no DB dependency.
 * Every write updates the DB row (so the admin UI has one durable source)
 * and the file cache together, in that order.
 */
class SettingsActivator implements ActivatorInterface
{
    public const SETTINGS_KEY = 'modules_settings';

    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;

        return $this->isActive($name) === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $flags = static::flags();
        $flags[Str::lower($name)] = $active;
        static::persist($flags);
    }

    public function delete(Module $module): void
    {
        $flags = static::flags();
        unset($flags[Str::lower($module->getName())]);
        static::persist($flags);
    }

    public function reset(): void
    {
        static::persist([]);
    }

    /**
     * Whether a module/slug is active. Unknown slugs default to enabled — every
     * feature carved out of the monolith was already live, so admins opt out
     * rather than having to opt back in after the upgrade.
     */
    public static function isActive(string $name): bool
    {
        $flags = static::flags();

        return (bool) ($flags[Str::lower($name)] ?? true);
    }

    /**
     * Fast, DB-independent read path: the bootstrap/cache file mirror.
     * Falls back to the `settings` table only when the file doesn't exist
     * yet (e.g. brand new install, nobody has ever saved a toggle) - that
     * DB read is itself wrapped in a try/catch so an unavailable database
     * never breaks the app just to answer "is this module on".
     */
    public static function flags(): array
    {
        $cachePath = static::cachePath();

        if (is_file($cachePath)) {
            $decoded = json_decode((string) file_get_contents($cachePath), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        try {
            $value = app(Setting::class)->get_setting(static::SETTINGS_KEY);
        } catch (\Throwable) {
            return [];
        }

        if ($value === '' || $value === null) {
            return [];
        }

        $flags = (array) $value;
        static::writeCache($flags);

        return $flags;
    }

    private static function persist(array $flags): void
    {
        app(Setting::class)->update_setting(static::SETTINGS_KEY, $flags, true);
        static::writeCache($flags);
    }

    private static function writeCache(array $flags): void
    {
        @file_put_contents(static::cachePath(), json_encode($flags));
    }

    private static function cachePath(): string
    {
        return function_exists('base_path')
            ? base_path('bootstrap/cache/modules_settings.json')
            : __DIR__.'/../../../bootstrap/cache/modules_settings.json';
    }
}
