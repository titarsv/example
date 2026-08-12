<?php

namespace Modules\PageImport\Services;

use App\Models\File as MediaFile;
use Illuminate\Support\Str;

/**
 * Перенос картинок и инлайн-SVG контента (уже найденных ArchiveParser'ом внутри <main>)
 * в медиатеку сайта — тем же способом, что и обычная загрузка через «Файловый менеджер»
 * (App\Models\File::createFile(), дедуп по md5-хэшу), см. план, стадия 0.4.
 *
 * Ассеты донора ВНЕ контентной области (фавикон, шрифты, css/js) сюда не попадают —
 * этим занимается разработчик вручную, см. «Контекст и цель» в docs/page-import-plan.md.
 */
class AssetImporter
{
    protected string $uploadsDir;
    protected string $uploadsPath;

    public function __construct(){
        $this->uploadsDir = env('UPLOADS_DIR', 'uploads');
        $this->uploadsPath = public_path().DIRECTORY_SEPARATOR.$this->uploadsDir;
    }

    /**
     * Переносит картинки контента (с уже резолвленным реальным путём на диске донора)
     * в медиатеку. Картинки, чей src не удалось резолвнуть в реальный файл (внешние урлы,
     * data:, битые ссылки — см. ArchiveParser::resolveAssetPath), молча пропускаются.
     *
     * @param array $images Результат ArchiveParser::parseHtmlFile()['images']
     * @return array<string, array{url: string, id: int}> Карта "src из разметки донора" =>
     *         новый url в /uploads + id записи медиатеки (нужен для значения oembed-поля на 1.3)
     */
    public function importImages(array $images): array{
        $map = [];

        foreach($images as $image){
            if(empty($image['path']) || !is_file($image['path'])){
                continue;
            }

            $file = $this->importDiskFile($image['path'], pathinfo($image['path'], PATHINFO_BASENAME));

            if($file !== null){
                $map[$image['src']] = ['url' => $this->publicUrl($file), 'id' => $file->id];
            }
        }

        return $map;
    }

    /**
     * Переносит инлайновые <svg> контента в медиатеку как самостоятельные .svg-файлы.
     * Сам markup при этом НЕ выбрасывается — он идёт в значение текстового поля на
     * стадии 1.3 (сборка схемы шаблона), здесь только физическая копия для «Медиа».
     *
     * @param array $svgs Результат ArchiveParser::parseHtmlFile()['svgs'] — список markup-строк
     * @return array<int, array{markup: string, file: MediaFile}>
     */
    public function importSvgs(array $svgs): array{
        $result = [];

        foreach($svgs as $index => $markup){
            $markup = trim($markup);

            if($markup === ''){
                continue;
            }

            $hash = md5($markup);
            $existing = MediaFile::where('hash', $hash)->first();

            if($existing !== null){
                $result[] = ['markup' => $markup, 'file' => $existing];
                continue;
            }

            $this->ensureUploadsDir();

            // Имя строим от хэша, а не от локального индекса внутри страницы — иначе разные
            // иконки на разных страницах архива будут регулярно называться одинаково
            // ("icon-1.svg" у каждой страницы своя) и упираться в случайный суффикс коллизии.
            $filename = $this->uniqueFilename('icon-'.substr($hash, 0, 8).'.svg');
            file_put_contents($this->uploadsPath.DIRECTORY_SEPARATOR.$filename, $markup);

            $file = (new MediaFile())->createFile([
                'title' => 'icon-'.substr($hash, 0, 8),
                'path' => $this->uploadsDir.'/'.$filename,
                'type' => 'svg',
                'hash' => $hash,
            ]);

            $result[] = ['markup' => $markup, 'file' => $file];
        }

        return $result;
    }

    /**
     * Переписывает src картинок в HTML-строке (уже сериализованной ArchiveParser'ом) на новые
     * URL из медиатеки — по точному совпадению исходного src, как он был в разметке донора.
     *
     * @param string $content HTML-контент (ArchiveParser::parseHtmlFile()['content'])
     * @param array<string, array{url: string, id: int}> $srcMap Карта из importImages()
     */
    public function rewriteImageSrcs(string $content, array $srcMap): string{
        foreach($srcMap as $originalSrc => $mapped){
            $content = str_replace('src="'.$originalSrc.'"', 'src="'.$mapped['url'].'"', $content);
            $content = str_replace("src='".$originalSrc."'", "src='".$mapped['url']."'", $content);
        }

        return $content;
    }

    /**
     * Копирует один файл (картинку донора) в /uploads с дедупом по md5-хэшу содержимого —
     * тот же принцип, что в MediaController::adminUploadAction, только источник — уже
     * лежащий на диске файл архива, а не Illuminate\Http\UploadedFile.
     */
    private function importDiskFile(string $sourcePath, string $originalName): ?MediaFile{
        $hash = md5_file($sourcePath);

        if($hash === false){
            return null;
        }

        $existing = MediaFile::where('hash', $hash)->first();

        if($existing !== null){
            return $existing;
        }

        $this->ensureUploadsDir();

        $filename = $this->uniqueFilename($originalName);
        copy($sourcePath, $this->uploadsPath.DIRECTORY_SEPARATOR.$filename);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return (new MediaFile())->createFile([
            'title' => pathinfo($originalName, PATHINFO_FILENAME),
            'path' => $this->uploadsDir.'/'.$filename,
            'type' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : $extension,
            'hash' => $hash,
        ]);
    }

    /**
     * Абсолютный (от корня сайта) URL уже созданной записи медиатеки.
     */
    private function publicUrl(MediaFile $file): string{
        return '/'.ltrim(str_replace('\\', '/', $file->path), '/');
    }

    private function ensureUploadsDir(): void{
        if(!is_dir($this->uploadsPath)){
            mkdir($this->uploadsPath, 0777, true);
        }
    }

    /**
     * Уникальное имя файла в /uploads — транслитерация + замена пробелов (как в
     * MediaController::generate_filename), при коллизии — случайный суффикс.
     */
    private function uniqueFilename(string $originalName): string{
        $originalName = str_replace(' ', '_', translit($originalName));

        if(!is_file($this->uploadsPath.DIRECTORY_SEPARATOR.$originalName)){
            return $originalName;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        do{
            $candidate = $base.'_'.Str::random(6).($extension !== '' ? '.'.$extension : '');
        }while(is_file($this->uploadsPath.DIRECTORY_SEPARATOR.$candidate));

        return $candidate;
    }
}