<?php

namespace App\Support\Modules;

use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

/**
 * Reads module on/off state from config/modules_settings.php (overridable
 * per environment via .env, e.g. MODULE_BLOG=false), so the same
 * module_active() helper works for real nwidart modules and for
 * in-core-only toggles (e.g. cart_checkout) that never became an actual
 * Modules/ package. Config is loaded before any ServiceProvider::register()
 * runs, so this is also safe to read at the point Nwidart\Modules\ModuleManifest
 * decides whether a module's provider gets registered at all - no database
 * dependency, no bootstrap-timing hazard.
 *
 * There is no admin UI for this anymore: toggling a module means editing
 * config/modules_settings.php (or the matching MODULE_* env var) and
 * clearing the config cache if one is in use. enable()/disable() and
 * friends exist only to satisfy ActivatorInterface (e.g. nwidart's own
 * `module:enable`/`module:disable` artisan commands) and intentionally
 * refuse to write anything at runtime.
 */
class SettingsActivator implements ActivatorInterface
{
    public function enable(Module $module): void
    {
        $this->refuseRuntimeWrite();
    }

    public function disable(Module $module): void
    {
        $this->refuseRuntimeWrite();
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;

        return $this->isActive($name) === $status;
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->refuseRuntimeWrite();
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $this->refuseRuntimeWrite();
    }

    public function delete(Module $module): void
    {
        $this->refuseRuntimeWrite();
    }

    public function reset(): void
    {
        $this->refuseRuntimeWrite();
    }

    /**
     * Whether a module/slug is active. Unknown slugs default to enabled — every
     * feature carved out of the monolith was already live, so admins opt out
     * rather than having to opt back in after the upgrade.
     */
    public static function isActive(string $name): bool
    {
        return (bool) config('modules_settings.'.Str::lower($name), true);
    }

    private function refuseRuntimeWrite(): void
    {
        throw new \RuntimeException(
            'Module toggles are controlled by config/modules_settings.php (or the matching MODULE_* env var), '.
            'not at runtime. Edit the file/env and run `php artisan config:clear` instead of module:enable/disable.'
        );
    }
}
