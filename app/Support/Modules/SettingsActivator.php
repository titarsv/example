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
        app(Setting::class)->update_setting(static::SETTINGS_KEY, $flags, true);
    }

    public function delete(Module $module): void
    {
        $flags = static::flags();
        unset($flags[Str::lower($module->getName())]);
        app(Setting::class)->update_setting(static::SETTINGS_KEY, $flags, true);
    }

    public function reset(): void
    {
        app(Setting::class)->update_setting(static::SETTINGS_KEY, [], true);
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
     * Reads the flags row. Defensively returns [] (== everything enabled) on
     * any failure — the module manifest reads activator status very early in
     * the request/console lifecycle (e.g. `package:discover`, before the DB
     * connection resolver or the settings table necessarily exist), so a
     * broken/unavailable database must never take the whole app down just to
     * answer "is this module on".
     */
    public static function flags(): array
    {
        try {
            $value = app(Setting::class)->get_setting(static::SETTINGS_KEY);
        } catch (\Throwable) {
            return [];
        }

        if ($value === '' || $value === null) {
            return [];
        }

        return (array) $value;
    }
}
