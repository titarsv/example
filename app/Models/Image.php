<?php

namespace App\Models;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class Image extends Entity
{
    protected $files_dir = 'thumbnails';
    protected $files_path = '/thumbnails/';
    protected $max_width = 3840;
    protected $max_height = 1920;

    protected $fillable = [
        'file_id',
        'path',
        'mime',
        'size',
        'width',
        'height',
        'crop'
    ];

    public static function boot(){
        parent::boot();

        self::deleting(function($model){
            if($model->size != 'full'){
                $file_path = public_path($model->path);
                if(is_file($file_path)){
                    unlink($file_path);
                }
            }
        });
    }

    public function file(){
        return $this->belongsTo('App\Models\File', 'file_id');
    }

    public function user_data(){
        return $this->hasOne('App\Models\UserData', 'image_id', 'id');
    }

    /**
     * URL изображения
     *
     * @return mixed|string
     */
    public function getUrlAttribute(){
        $url = env('APP_URL').'/'.$this->path;
        $url = str_replace('\\', '/', $url);

        return $url;
    }

    /**
     * Создание миниатюры изображения
     *
     * @param string $size
     * @param string $crop
     *
     * @return $this
     */
    public function createThumbnail($size = 'full', $crop = 'original'){
        if(!is_array($size)){
            $size = explode('_', $size);
        }

        if(count($size) == 2){
            $path = $this->updateImageSize($this->path, $size[0], $size[1], $crop);
            if(!empty($path)){
                $image_data = getimagesize(public_path($path));
                return $this->find($this->insertGetId([
                    'file_id' => $this->file_id,
                    'path' => $path,
                    'mime' => $image_data['mime'],
                    'size' => implode('_', $size),
                    'width' => $image_data[0],
                    'height' => $image_data[1],
                    'crop' => $crop
                ]));
            }
        }

        return $this;
    }

    /**
     * Создание Webp копии изображения
     *
     * @return null
     */
    public function createWebp(){
        $file_path = public_path($this->path);
        if(is_file($file_path) && function_exists('imagewebp')){
            $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION ));
            $webp_name = str_replace('.'.$extension, '_'.$extension.'.webp', basename($this->path));
            $webp_path = public_path($this->files_path.$webp_name);

            $image = $this->imageCreateFromFile($file_path);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagewebp($image, $webp_path);
            imagedestroy($image);

            return $this->find($this->insertGetId([
                'file_id' => $this->file_id,
                'path' => $this->files_dir.'/'.$webp_name,
                'mime' => 'image/webp',
                'size' => $this->size,
                'width' => $this->width,
                'height' => $this->height,
                'crop' => $this->crop,
            ]));
        }

        return null;
    }

    /**
     * Создание avif-изображения
     *
     * @return mixed|null
     */
    public function createAvif(){
        $file_path = public_path($this->path);
        if(is_file($file_path) && function_exists('imageavif')){
            $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION ));
            $avif_name = str_replace('.'.$extension, '_'.$extension.'.avif', basename($this->path));
            $avif_path = public_path($this->files_path.$avif_name);

            $image = $this->imageCreateFromFile($file_path);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagewebp($image, $avif_path);
            imagedestroy($image);

            return $this->find($this->insertGetId([
                'file_id' => $this->file_id,
                'path' => $this->files_dir.'/'.$avif_name,
                'mime' => 'image/avif',
                'size' => $this->size,
                'width' => $this->width,
                'height' => $this->height,
                'crop' => $this->crop,
            ]));
        }

        return null;
    }

    /**
     * Динамическое создание объекта изображения из файла
     *
     * @param $filename
     * @return resource
     */
    public function imageCreateFromFile($filename){
        $mime = mime_content_type($filename);
        if($mime == 'image/avif'){
            return imagecreatefromavif($filename);
        }elseif($mime == 'image/webp'){
            return imagecreatefromwebp($filename);
        }elseif($mime == 'image/jpeg'){
            return imagecreatefromjpeg($filename);
        }elseif($mime == 'image/png'){
            return imagecreatefrompng($filename);
        }elseif($mime == 'image/gif'){
            return imagecreatefromgif($filename);
        }

        switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION ))) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($filename);
                break;

            case 'png':
                return imagecreatefrompng($filename);
                break;

            case 'gif':
                return imagecreatefromgif($filename);
                break;

            case 'webp':
                return imagecreatefromwebp($filename);
                break;

            case 'avif':
                return imagecreatefromavif($filename);
                break;
        }

        return null;
    }

    /**
     * Создание изображения заданного размера
     * @param string $href Имя файла
     * @param int $w Ширина
     * @param int $h Высота
     * @param string $method (contain|cover|crop)/(уместить/заполнить/обрезать)
     * @return string Имя созданного файла
     */
    public function updateImageSize($href = '', $w = 1, $h = 1, $method = 'contain'){
        // 1. Инициализация и пути
        if(empty($href)){
            $href = $this->path;
        }
        $basename = basename($href);
        $name_parts = explode('.', $basename);
        $extension = end($name_parts);

        $path = public_path($this->path);
        $new_name = '';

        if(!@getimagesize($path)) {
            return $new_name;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->gd()->read($path);
            $original_width = $image->width();
            $original_height = $image->height();

            // 2. Условие выхода
            if ($method != 'contain' && ($original_width < $w || $original_height < $h)) {
                return $new_name;
            }

            $result_width = $w;
            $result_height = $h;
            $save_method = 'default';

            // 3. Логика изменения размера
            switch ($method) {
                case 'contain':
                    // V3: scaleDown() уменьшает изображение до WxH, сохраняя пропорции.
                    // Не выполняет увеличение (upsizing).
                    $image->scaleDown($w, $h);

                    $result_width = $image->width();
                    $result_height = $image->height();
                    break;

                case 'cover':
                case 'crop':
                    // V3: cover() масштабирует изображение, чтобы оно ПОКРЫЛО WxH,
                    // и обрезает лишние части (аналог V2 fit).
                    // Убедитесь, что $w и $h больше 0 перед вызовом!
                    if ($w > 0 && $h > 0) {
                        $image->cover($w, $h);
                    }

                    $result_width = $w;
                    $result_height = $h;
                    break;
            }

            // 4. Сохранение файла
            $new_name = $this->save_image_file(
                $image,
                $original_width,
                $original_height,
                $result_width,
                $result_height,
                $extension,
                $basename,
                $save_method
            );

        } catch (\Exception $e) {
            // Обработка ошибок
            return $new_name;
        }

        return $new_name;
    }

    /**
     * Вспомогательная функция для именования и сохранения обработанного файла.
     * В отличие от оригинальной версии, здесь не происходит дополнительных манипуляций.
     */
    public function save_image_file($image, $original_width, $original_height, $result_width, $result_height, $extension, $basename, $method = ''){
        // Проверка: если размеры не изменились, возвращаем пустую строку.
        if($original_width == $result_width && $original_height == $result_height){
            return '';
        }

        // Создание нового имени файла (например: image_400x300.jpg)
        $new_name = str_replace('.'.$extension, '_'.$result_width.'x'.$result_height.'.'.$extension, $basename);
        $new_path = public_path($this->files_path . $new_name);

        $image->save($new_path, 75);

        // Возврат публичного пути
        return $this->files_dir.'/'.$new_name;
    }
}
