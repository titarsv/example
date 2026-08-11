<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ThemeMakeCommand extends Command
{
    protected $signature = 'theme:make {name : Slug for the new theme, e.g. acme}
                            {--from=base : Existing theme to copy as the starting point}';

    protected $description = 'Scaffold a new storefront theme by copying an existing one (resources/themes/{name})';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $from = $this->option('from');

        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
            $this->error('Theme name must be lowercase letters, digits and hyphens only.');

            return self::FAILURE;
        }

        $themesPath = config('theme.path');
        $sourcePath = $themesPath.'/'.$from;
        $targetPath = $themesPath.'/'.$name;

        if (!$files->isDirectory($sourcePath)) {
            $this->error("Source theme \"{$from}\" not found at {$sourcePath}");

            return self::FAILURE;
        }

        if ($files->isDirectory($targetPath)) {
            $this->error("Theme \"{$name}\" already exists at {$targetPath}");

            return self::FAILURE;
        }

        $files->copyDirectory($sourcePath, $targetPath);

        $this->info("Created theme \"{$name}\" from \"{$from}\" at resources/themes/{$name}");
        $this->line('Next steps:');
        $this->line('  1. Delete anything under resources/themes/'.$name.' that you don\'t need to change — it falls back to "'.config('theme.fallback').'" automatically.');
        $this->line('  2. Set ACTIVE_THEME='.$name.' in .env');
        $this->line('  3. Add "'.$name.'" to the `available` list in config/theme.php');
        $this->line('  4. Run `npm run dev` (or `npm run prod`) to build its assets into public/themes/'.$name);

        return self::SUCCESS;
    }
}
