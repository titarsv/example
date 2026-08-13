<?php

namespace Modules\ThemeImport\Services;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Находит корень исходников верстальщика (app/) внутри распакованного архива — донор может
 * упаковать zip с произвольным именем верхнеуровневой папки (или вообще без неё), поэтому ищем
 * характерную структуру (app/assets/templates/layouts), а не полагаемся на фиксированную глубину.
 * См. docs/dynamic-page-import-plan.md, «Источник архива: не dist/, а исходники верстальщика».
 */
class SourceLocator
{
    /**
     * @return array{app: string, layouts: string, stylesheets: string, images: string|null,
     *         fonts: string|null, modules: string|null}|null null, если структура не похожа на
     *         исходники верстальщика (нет .../assets/templates/layouts).
     */
    public function locate(string $extractedPath): ?array
    {
        $layoutsDir = $this->findDir($extractedPath, ['assets', 'templates', 'layouts']);

        if($layoutsDir === null){
            return null;
        }

        // app/ — на два уровня выше найденного .../assets/templates/layouts.
        $appDir = dirname($layoutsDir, 3);
        $assetsDir = dirname($layoutsDir, 2);

        return [
            'app' => $appDir,
            'layouts' => $layoutsDir,
            'stylesheets' => $assetsDir.DIRECTORY_SEPARATOR.'stylesheets',
            'images' => is_dir($assetsDir.DIRECTORY_SEPARATOR.'images') ? $assetsDir.DIRECTORY_SEPARATOR.'images' : null,
            'fonts' => is_dir($assetsDir.DIRECTORY_SEPARATOR.'fonts') ? $assetsDir.DIRECTORY_SEPARATOR.'fonts' : null,
            'appJs' => is_file($appDir.DIRECTORY_SEPARATOR.'app.js') ? $appDir.DIRECTORY_SEPARATOR.'app.js' : null,
            'modules' => is_dir($appDir.DIRECTORY_SEPARATOR.'modules') ? $appDir.DIRECTORY_SEPARATOR.'modules' : null,
        ];
    }

    /**
     * Рекурсивно ищет директорию с указанной цепочкой вложенных имён (например
     * ['assets','templates','layouts'] — для .../assets/templates/layouts на любой глубине,
     * донор может завернуть архив в произвольную верхнеуровневую папку). node_modules/.git не
     * обходятся вовсе — донор не должен был класть node_modules в архив, но подстрахуемся, обход
     * тысяч файлов там дорогой и бессмысленный.
     *
     * @param string[] $segments
     */
    private function findDir(string $root, array $segments): ?string
    {
        if(!is_dir($root)){
            return null;
        }

        $first = $segments[0];

        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            function(SplFileInfo $current): bool {
                return !($current->isDir() && in_array($current->getFilename(), ['node_modules', '.git'], true));
            }
        );

        $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        foreach($iterator as $fileInfo){
            if($fileInfo->isDir() && $fileInfo->getFilename() === $first){
                $candidate = $this->matchesRemainingSegments($fileInfo->getPathname(), array_slice($segments, 1));

                if($candidate !== null){
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Проверяет, что оставшиеся сегменты цепочки есть строго ВНУТРИ найденного кандидата (не
     * где-то ещё в дереве) — иначе, например, произвольная папка "assets" в другом месте архива
     * (донор мог использовать это имя не только под app/) ложно засчиталась бы совпадением.
     *
     * @param string[] $remaining
     */
    private function matchesRemainingSegments(string $path, array $remaining): ?string
    {
        foreach($remaining as $segment){
            $next = $path.DIRECTORY_SEPARATOR.$segment;

            if(!is_dir($next)){
                return null;
            }

            $path = $next;
        }

        return $path;
    }
}