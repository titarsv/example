<?php

namespace App\Models;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GenerateWebpJob;
use App\Jobs\GenerateAvifJob;
use App\Helpers\Helper;
use Dompdf\Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use App\Jobs\GenerateVideoThumbnail;
use Modules\Blog\Models\Blog;

class File extends Model
{
    use SoftDeletes, HasLocalizationTrait;
    protected $files_path =  '/uploads/';
    protected $max_width = 3840;
    protected $max_height = 1920;

    protected $fillable = [
        'path',
        'filename',
        'alt',
        'title',
        'description',
        'type',
        'author',
        'data',
        'gd_id',
    ];

    protected $localized_fields = [
        'alt',
        'title',
        'description'
    ];

    protected $dates = ['deleted_at'];

    protected $table = 'files';

    public function __construct(){
        parent::__construct();
        $this->files_path = '/'.env('UPLOADS_DIR', 'uploads').'/';
    }

    public static function boot()
    {
        parent::boot();

        self::deleting(function ($model) {
            Gallery::where('file_id', $model->id)->delete();
        });
    }

    public function images(){
        return $this->hasMany('App\Models\Image', 'file_id');
    }

    public function getDataAttribute(){
        if(is_array($this->attributes['data']))
            return $this->attributes['data'];

        return (array)json_decode($this->attributes['data'], true);
    }

    public function createFile($data){
        if(!isset($data['data'])){
            $data['data'] = json_encode([]);
        }

        $file = $this->find($this->insertGetId($data));
        if($file->type == 'image'){
            $file->createImage();
        }
        $file->data = $file->getFullData();
        $file->save();

        return $file;
    }

    public function updateData($data = null){
        $full_data = $this->data;
        if(empty($full_data) || ($this->type == 'image' && (empty($full_data['width']) || empty($full_data['height']))))
            $full_data = $this->getFullData();

        if(!empty($data)){
            if(!empty($data['subtype']))
                $full_data['subtype'] = $data['subtype'];
            if(!empty($data['sizes'])){
                foreach($data['sizes'] as $size => $size_data){
                    if(!isset($full_data['sizes'][$size])){
                        $full_data['sizes'][$size] = $size_data;
                    }else{
                        foreach($size_data as $crop => $links){
                            if(!isset($full_data['sizes'][$size][$crop])){
                                $full_data['sizes'][$size][$crop] = $links;
                            }else{
                                foreach($links as $type => $link){
                                    $full_data['sizes'][$size][$crop][$type] = '/'.ltrim($link, '/');
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->data = json_encode($full_data, JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public function fileData($path = null){
        if(empty($path))
            $filepath = public_path().'/'.str_replace(['/', '\\'], '/', $this->path);
        else
            $filepath = public_path().'/'.str_replace(['/', '\\'], '/', $path);
        if(is_file($filepath)){
            try {
                $data                          = getimagesize( $filepath );
                $data['filesize']              = filesize( $filepath );
                $data['filesizeHumanReadable'] = $this->sizeFormat( $data['filesize'] );
            }catch (Exception $e){
                $data = [0 => 0, 1 => 0, 'mime' => ' / ', 'filesize' => '', 'filesizeHumanReadable' => ''];
            }
        }else{
            $data = [0 => 0, 1 => 0, 'mime' => ' / ', 'filesize' => '', 'filesizeHumanReadable' => ''];
        }

        return $data;
    }

    public function sizeFormat($bytes, $decimals = 0){
        $quant = array(
            'TB' => 1024*1024*1024*1024,
            'GB' => 1024*1024*1024,
            'MB' => 1024*1024,
            'KB' => 1024,
            'B'  => 1,
        );

        if ( 0 === $bytes ) {
            return number_format( 0, abs(intval( $decimals )) ) . ' B';
        }

        foreach ( $quant as $unit => $mag ) {
            if ( doubleval( $bytes ) >= $mag ) {
                return number_format( $bytes / $mag, $decimals ) . ' ' . $unit;
            }
        }

        return false;
    }

    public function getFullData(){
        $url = str_replace(env('APP_URL'), '', $this->url());
        $filedata = $this->fileData();
        if(empty($filedata['mime'])){
            $filedata['mime'] = $this->type === 'video' ? 'video/mp4' : 'undefined/file';
            $filedata[0] = 1;
            $filedata[1] = 1;
        }
        $mime = explode('/', $filedata['mime']);
        $sizes = [];
        $s = $this->images;
        $user = Sentinel::getUser();
        if($s->count()) {
            foreach ($s as $size) {
                $sizes[$size->size == 'full' ? $size->size : $size->id.'_'.$size->size] = [
                    'url' => str_replace(env('APP_URL'), '', $size->url),
                    'height' => $size->height,
                    'width' => $size->width,
                    'orientation' => 'landscape',
                ];
            }
        }
        if($s->count()) {
            foreach($s as $size){
                $sizes[$size->size == 'full' ? $size->size : $size->size][$size->crop][$size->mime == 'image/webp' ? 'webp' : 'url'] = str_replace(env('APP_URL'), '', $size->url);
            }
        }
        $filename = basename($this->files_path);

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'filename' => $filename,
            'url' => str_replace(env('APP_URL'), '', $url),
            'link' => $url,
            'alt' => $this->alt,
            'author' => 'admin',
            'description' => $this->description,
            'caption' => '',
            'name' => $filename,
            'status' => 'inherit',
            'uploadedTo' => 0,
            'date' => isset($this->created_at) ? $this->created_at->timestamp : time(),
            'modified' => empty($this->updated_at) ? (isset($this->created_at) ? $this->created_at->timestamp : time()) : $this->updated_at->timestamp,
            'menuOrder' => 0,
            'mime' => empty(trim($filedata['mime'])) ? 'image/jpeg' : trim($filedata['mime']),
            'type' => empty(trim($mime[0])) ? 'image' : trim($mime[0]),
            'subtype' => $mime[1],
            'icon' => $this->type === 'video' ? '/images/larchik/video.png' : '/images/larchik/default.png',
            'dateFormatted' =>  empty($this->updated_at) ? (!empty($this->created_at) ? $this->created_at->format('d.m.Y') : date('d.m.Y')) : $this->updated_at->format('d.m.Y'),
            'nonces' => [
                "update" => '',
                "delete" => $this->id > 1 ? '77118a539c' : '',
                "edit" => ''
            ],
            'editLink' => '',
            'meta' => false,
            'authorName' => !empty($user) ? $user->email : '',
            'filesizeInBytes' => $filedata['filesize'],
            'filesizeHumanReadable' => $filedata['filesizeHumanReadable'],
            'context' => '',
            'height' => $filedata[1],
            'width' => $filedata[0],
            'orientation' => "landscape",
            'sizes' => $sizes,
            'compat' => [
                'item' => '',
                'meta' => '',
            ],
        ];

        $this->load('localization');
        foreach(config('app.locales') as $locale){
            $data['title_'.$locale] = $this->localize($locale, 'title');
            $data['alt_'.$locale] = $this->localize($locale, 'alt');
            $data['description_'.$locale] = $this->localize($locale, 'description');
        }

        if($this->type === 'video'){
            $thumbnail = $this->captureFrame();
            if(!empty($thumbnail)){
                $data['thumbnail'] = $thumbnail;
                $data['icon'] = '/'.$thumbnail;
            }
        }

        return $data;
    }

    public function captureFrame()
    {
        if(!is_file(public_path('thumbnails/'.$this->filename.'.jpg'))){
            GenerateVideoThumbnail::dispatch($this->id);

            return null;
        }else{
            return 'thumbnails/'.$this->filename.'.jpg';
        }
    }

    public function getPdfFullData(){
        $url = $this->files_path . $this->href;
        $filepath = public_path() . $this->files_path . $this->href;
        $filesize = filesize($filepath);
        $sizes = [];
        $s = json_decode($this->sizes);
        if(is_object($s)) {
            foreach ($s as $name => $size) {
                $sizes[$name] = [
                    'url' => str_replace(env('APP_URL'), '', $this->url($name)),
                    'height' => $size->h,
                    'width' => $size->w,
                    'orientation' => 'landscape',
                ];
            }
        }
        $filename = basename($this->files_path);
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'filename' => $this->href,
            'url' => str_replace(env('APP_URL'), '', $url),
            'link' => $url,
            'alt' => $this->alt,
            'author' => 'admin',
            'description' => $this->description,
            'caption' => '',
            'name' => $filename,
            'status' => 'inherit',
            'uploadedTo' => 0,
            'date' => strtotime($this->created_at),
            'modified' => empty($this->updated_at) ? strtotime($this->created_at) : strtotime($this->updated_at),
            'menuOrder' => 0,
            'mime' => 'application/pdf',
            'type' => 'application',
            'subtype' => 'pdf',
            'icon' => '/images/larchik/default.png',
            'dateFormatted' =>  date('d.m.Y', empty($this->updated_at) ? strtotime($this->created_at) : strtotime($this->updated_at)),
            'nonces' => [
                "update" => '',
                "delete" => $this->id > 1 ? '77118a539c' : '',
                "edit" => ''
            ],
            'editLink' => '',
            'meta' => false,
            'authorName' => 'admin',
            'filesizeInBytes' => $filesize,
            'filesizeHumanReadable' => $this->size_format($filesize),
            'context' => '',
            'height' => 0,
            'width' => 0,
            'orientation' => "landscape",
            'sizes' => $sizes,
            'compat' => [
                'item' => '',
                'meta' => '',
            ],
        ];

        return $data;
    }

    /**
     * Загрузка по URL
     *
     * @param $url
     * @param int $author
     * @return mixed
     */
    public function uploadFromUrlImages($url, $author = 1){
        $destinationPath = public_path().$this->files_path;
        $parts = explode('/', $url);
        $parts = explode('?', end($parts));
        $originalName = $parts[0];
        $newFileName = $originalName;

        $file_data = file_get_contents($url);
        $pattern = "/^content-type\s*:\s*(.*)$/i";
        if(($header = array_values(preg_grep($pattern, $http_response_header))) && (preg_match($pattern, $header[0], $match) !== false)){
            $contentType = explode('/', $match[1]);
        }else{
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_exec($ch);
            $contentType = explode('/', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        }
        $hash = md5($file_data);

        $extension = $contentType[1];
        if($extension == 'jpeg')
            $extension = 'jpg';
        if(strpos($newFileName, '.'.$extension) != strlen($newFileName) - strlen($extension) - 1){
            $newFileName .= '.'.$extension;
        }

        $isset = $this->where('hash', $hash)->first();

        if(!empty($isset)){
            return $isset;
        }elseif(is_file($destinationPath.'\\'.$newFileName)){
            $newFileName = $this->generate_filename($newFileName, $extension, $path = '');
        }

        file_put_contents($destinationPath.$newFileName, $file_data);

        $file = $this->createFile([
            'title' => $originalName,
            'path' => substr($this->files_path, 1).$newFileName,
            'type' => $contentType[0],
            'hash' => $hash,
            'author' => $author
        ]);

        return $file;
    }

    /**
     * Загрузка по пути
     *
     * @param $path
     * @return mixed
     */
    public function uploadFromPathImages($path){
        $destinationPath = public_path().$this->files_path;
        $originalName = basename($path);
        $newFileName = $originalName;

        $file_data = file_get_contents($path);

        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = explode(';', finfo_file($finfo, $path));
        finfo_close($finfo);
        $contentType = explode('/', $mimetype[0]);
        $hash = md5($file_data);

        $extension = $contentType[1];
        if($extension == 'jpeg')
            $extension = 'jpg';
        if(strpos($newFileName, '.'.$extension) != strlen($newFileName) - strlen($extension) - 1){
            $newFileName .= '.'.$extension;
        }

        $isset = $this->where('hash', $hash)->first();

        if(!empty($isset)){
            return $isset;
        }elseif(is_file($destinationPath.'\\'.$newFileName)){
            $newFileName = $this->generate_filename($newFileName, $extension, $path);
        }

        file_put_contents($destinationPath.$newFileName, $file_data);

        $file = $this->createFile([
            'title' => $originalName,
            'path' => substr($this->files_path, 1).$newFileName,
            'type' => $contentType[0],
            'hash' => $hash
        ]);

        return $file;
    }

    /**
     * Генерация уникального имени файла
     *
     * @param $name
     * @param $try_extension
     * @param string $path
     * @return mixed
     */
    public function generate_filename($name, $try_extension, $path = ''){
        if(empty($path))
            $path = public_path().$this->files_path;

        $originalName = str_replace(' ', '_', translit($name));

        if(is_file($path.'/'.$originalName)) {
            $paths = explode('.', $originalName);
            $extension = end($paths);

            $i = 2;
            $originalName = preg_replace('/(.+)(_\(\d+\))?\.'.$extension.'/', '$1_('.$i.').'.$try_extension, $originalName);
            while(is_file($path.'/'.$originalName)){
                $originalName = preg_replace('/(.+)(_\(\d+?\))\.'.$extension.'/', '$1_('.$i.').'.$try_extension, $originalName);
                $i++;
            }
        }

        return $originalName;
    }

    /**
     * Создание основного изображения
     *
     * @return null
     */
    public function createImage(){
        if($this->type == 'image'){
            $filedata = $this->fileData();
            $images = new Image();
            $id = $images->insertGetId([
                'file_id' => $this->id,
                'path' => $this->path,
                'mime' => $filedata['mime'],
                'size' => 'full',
                'width' => $filedata[0],
                'height' => $filedata[1],
                'crop' => 'original',
            ]);

            return $id;
        }

        return null;
    }

    /**
     * Получение нужного размера изображения
     *
     * @param string $size Размер изображения ('full', 'product', 'product_list', 'blog', [100, 100])
     * @param string $crop (contain|cover|crop)/(уместить/заполнить/обрезать)
     *
     * @return mixed|string
     */
    public function url($size = 'full', $crop = 'cover'){
        if($size == 'full' || $this->type !== 'image'){
            $url = env('APP_URL').'/'.$this->path;
        }else{
            if(isset($this->data['sizes'][implode('_', $size)][$crop]) && !empty($this->data['sizes'][implode('_', $size)][$crop]['url'])){
                return '/'.ltrim(str_replace(env('APP_URL'), '', $this->data['sizes'][implode('_', $size)][$crop]['url']), '/');
            }
            $thumbnail = $this->getOrCreateThumbnail($size, $crop);
            $url = $thumbnail->url;
        }
        $url = str_replace('\\', '/', $url);

        return $url;
    }

    /**
     * Получение миниатюры
     *
     * @param string $type
     * @param string $size
     * @param string $crop
     * @param array $attributes
     * @param false $lazy
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string|null
     */
    public function getOriginal($type = 'url', $size = 'full', $crop = 'cover', $attributes = [], $lazy = false){
        if($this->type === 'image'){
            if(is_array($size)){
                $size = implode('_', $size);
            }

            if(!empty($this->data['sizes'])){
                if(isset($this->data['subtype']))
                    $original_mime = $this->data['subtype'];
                if(isset($this->data['sizes'][$size][$crop]['url']))
                    $thumbnail_url = $this->data['sizes'][$size][$crop]['url'];
            }

            if(!isset($original_mime) || !isset($thumbnail_url)){
                if(isset($thumbnail_url)){
                    $thumbnail = $this->images()->where('path', ltrim(str_replace(env('APP_URL'), '', $thumbnail_url), '/'))->first();
                }else{
                    $thumbnail = $this->getOrCreateThumbnail($size, $crop);
                }

                $original_mime = $thumbnail->mime;
                $thumbnail_path = $thumbnail->path;
                $thumbnail_url = $thumbnail->url;

                $data = [
                    'sizes' => [
                        $size => [
                            $crop => [
                                'url' => $thumbnail_path
                            ]
                        ]
                    ],
                    'subtype' => $original_mime
                ];

                $this->updateData($data);
            }

            if($type == 'html'){
                if(empty($thumbnail_url)){
                    $thumbnail_url = $this->link();
                }

                $attributes['src'] = $thumbnail_url;

                return view('public.layouts.image')
                    ->with('attributes', $attributes)->render();
            }
        }

        if($type == 'url'){
            return !empty($thumbnail_url) ? $thumbnail_url : null;
        }

        return null;
    }

    /**
     * Получение webp миниатюры
     *
     * @param string $type
     * @param string $size
     * @param string $crop
     * @param array $attributes
     * @param false $lazy
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string|null
     */
    public function getWebp($type = 'url', $size = 'full', $crop = 'cover', $attributes = [], $lazy = false){
        if($this->type === 'image'){
            if(is_array($size)){
                $size = implode('_', $size);
            }

            if(!empty($this->data['sizes'])){
                if(isset($this->data['subtype']))
                    $original_mime = $this->data['subtype'];
                if(isset($this->data['sizes'][$size][$crop]['url']))
                    $thumbnail_url = $this->data['sizes'][$size][$crop]['url'];
                if(isset($this->data['sizes'][$size][$crop]['webp']))
                    $webp_url = $this->data['sizes'][$size][$crop]['webp'];
            }

            if(!isset($original_mime) || !isset($thumbnail_url) || !isset($webp_url)){
                if(isset($thumbnail_url)){
                    $thumbnail = $this->images()->where('path', ltrim(str_replace(env('APP_URL'), '', $thumbnail_url), '/'))->first();
                }else{
                    $thumbnail = $this->getOrCreateThumbnail($size, $crop);
                }

                $original_mime = $thumbnail->mime;
                $thumbnail_path = $thumbnail->path;
                $thumbnail_url = '/'.ltrim(str_replace(config('app.url'), '', $thumbnail->url), '/');

                $data = [
                    'sizes' => [
                        $size => [
                            $crop => [
                                'url' => $thumbnail_path
                            ]
                        ]
                    ],
                    'subtype' => $original_mime
                ];

                if(!isset($webp_url)){
                    $webp = $this->images()->where('size', $thumbnail->size)->where('crop', $thumbnail->crop)->where('mime', 'image/webp')->first();
                    if(empty($webp)){
                        if(env('APP_DEBUG')){
                            $webp = $thumbnail->createWebp();
                            if(!empty($webp)){
                                $webp_url = '/'.ltrim(str_replace(config('app.url'), '', $webp->url), '/');
                                $data['sizes'][$size][$crop]['webp'] = $webp_url;
                            }
                        }else{
                            GenerateWebpJob::dispatch($thumbnail);
                        }
                    }else{
                        $webp_url = '/'.ltrim(str_replace(config('app.url'), '', $webp->url), '/');
                        $data['sizes'][$size][$crop]['webp'] = $webp_url;
                    }
                }

                $this->updateData($data);
            }

            if($type == 'html'){
                if(!empty($webp_url)){
                    $attributes['src'] = $webp_url;
                }else{
                    if(empty($thumbnail_url)){
                        $thumbnail_url = $this->link();
                    }
                    $attributes['src'] = $thumbnail_url;
                }

                return view('public.layouts.image')
                    ->with('attributes', $attributes)
                    ->render();
            }
        }

        if($type == 'url'){
            return !empty($webp_url) ? $webp_url : null;
        }

        return null;
    }

    /**
     * Получение avif миниатюры
     *
     * @param string $type
     * @param string $size
     * @param string $crop
     * @param array $attributes
     * @param bool $lazy
     * @return array|null|string
     * @throws \Throwable
     */
    public function getAvif($type = 'url', $size = 'full', $crop = 'cover', $attributes = [], $lazy = false){
        if($this->type === 'image'){
            if(is_array($size)){
                $size = implode('_', $size);
            }

            if(!empty($this->data['sizes'])){
                if(isset($this->data['subtype']))
                    $original_mime = $this->data['subtype'];
                if(isset($this->data['sizes'][$size][$crop]['url']))
                    $thumbnail_url = $this->data['sizes'][$size][$crop]['url'];
                if(isset($this->data['sizes'][$size][$crop]['avif']))
                    $avif_url = $this->data['sizes'][$size][$crop]['avif'];
            }
            if(!isset($original_mime) || !isset($thumbnail_url) || !isset($avif_url)){
                if(isset($thumbnail_url)){
                    $thumbnail = $this->images()->where('path', ltrim($thumbnail_url, '/'))->first();
                }else{
                    $thumbnail = $this->getOrCreateThumbnail($size, $crop);
                }

                $original_mime = $thumbnail->mime;
                $thumbnail_path = $thumbnail->path;
                $thumbnail_url = '/'.ltrim(str_replace(config('app.url'), '', $thumbnail->url), '/');

                $data = [
                    'sizes' => [
                        $size => [
                            $crop => [
                                'url' => $thumbnail_path
                            ]
                        ]
                    ],
                    'subtype' => $original_mime
                ];

                if(!isset($avif_url)){
                    $avif = $this->images()->where('size', $thumbnail->size)->where('crop', $thumbnail->crop)->where('mime', 'image/avif')->first();
                    if(empty($avif)){
                        if(env('APP_DEBUG')){
                            $avif = $thumbnail->createAvif();
                            if(!empty($avif)){
                                $avif_url = '/'.ltrim(str_replace(config('app.url'), '', $avif->url), '/');
                                $data['sizes'][$size][$crop]['avif'] = $avif_url;
                            }
                        }else{
                            GenerateAvifJob::dispatch($thumbnail);
                        }
                    }else{
                        $avif_url = '/'.ltrim(str_replace(config('app.url'), '', $avif->url), '/');
                        $data['sizes'][$size][$crop]['avif'] = $avif_url;
                    }
                }

                $this->updateData($data);
            }

            if($type == 'html'){
                if(!empty($avif_url)){
                    $attributes['src'] = $avif_url;
                }else{
                    if(empty($thumbnail_url)){
                        $thumbnail_url = $this->link();
                    }
                    $attributes['src'] = $thumbnail_url;
                }

                return view('public.layouts.image')
                    ->with('attributes', $attributes)
                    ->render();
            }
        }

        if($type == 'url'){
            return !empty($avif_url) ? $avif_url : null;
        }

        return null;
    }

    /**
     * Ссылка на webp изображение нужного размера
     *
     * @param string $size Размер изображения ('full', 'product', 'product_list', 'blog', [100, 100])
     * @param string $crop (contain|cover|crop)/(уместить/заполнить/обрезать)
     *
     * @return mixed|string
     */
    public function url_webp($size = 'full', $crop = 'cover'){
        return $this->getWebp('url', $size, $crop);
    }

    /**
     * Ссылка на avif изображение нужного размера
     *
     * @param string $size Размер изображения ('full', 'product', 'product_list', 'blog', [100, 100])
     * @param string $crop (contain|cover|crop)/(уместить/заполнить/обрезать)
     * @return array|null|string
     * @throws \Throwable
     */
    public function url_avif($size = 'full', $crop = 'cover'){
        return $this->getAvif('url', $size, $crop);
    }

    /**
     * Вывод оптимизированного изображения
     *
     * @param string $size
     * @param array $attributes
     * @param bool $lazy
     * @param string $crop
     *
     * @return array|string
     * @throws \Throwable
     */
    public function webp($size = 'full', $attributes = [], $lazy = false, $crop = 'cover'){
        return $this->getWebp('html', $size, $crop, $attributes, $lazy);
    }

    /**
     * Вывод оптимизированного изображения
     *
     * @param string $size
     * @param array $attributes
     * @param bool $lazy
     * @param string $crop
     *
     * @return array|string
     * @throws \Throwable
     */
    public function avif($size = 'full', $attributes = [], $lazy = false, $crop = 'cover'){
        return $this->getAvif('html', $size, $crop, $attributes, $lazy);
    }

    /**
     * Изображение в base64
     *
     * @param string $size
     * @param string $crop
     * @param false $webp
     * @return mixed|string|null
     */
    public function base64($size = 'full', $crop = 'cover', $webp = false){
        $data = '';
        if($webp){
            $webp_url = $this->url_webp($size, $crop);

            if(!empty($webp_url)){
                $path = ltrim(str_replace('\\', '/', preg_replace("/^".str_replace("/", "\/", env('APP_URL'))."\/(.+)/i", '$1', $webp_url)), '/');

                if(is_file($path))
                    $data = 'data:image/webp;base64,'.base64_encode(file_get_contents(public_path(str_replace(env('APP_URL'), '', $path))));
            }
        }else{
            if($size == 'full' || $this->type !== 'image'){
                $path = ltrim(str_replace('\\', '/', $this->path), '/');
                if(is_file($path))
                    $data = 'data:'.$this->data['mime'].';base64,'.base64_encode(file_get_contents(public_path(str_replace(env('APP_URL'), '', $path))));
            }else{
                if(isset($this->data['sizes'][implode('_', $size)][$crop]) && !empty($this->data['sizes'][implode('_', $size)][$crop]['url'])){
                    $path = ltrim(str_replace('\\', '/', preg_replace("/^".str_replace("/", "\/", env('APP_URL'))."\/(.+)/i", '$1', $this->data['sizes'][implode('_', $size)][$crop]['url'])), '/');
                    if(is_file($path))
                        $data = 'data:'.$this->data['mime'].';base64,'.base64_encode(file_get_contents(public_path(str_replace(env('APP_URL'), '', $path))));
                }

                $thumbnail = $this->getOrCreateThumbnail($size, $crop);
                $path = ltrim(str_replace('\\', '/', $thumbnail->path), '/');
                if(is_file($path))
                    $data = 'data:'.$thumbnail->mime.';base64,'.base64_encode(file_get_contents(public_path(str_replace(env('APP_URL'), '', $path))));
            }
        }

        return !empty($data) ? $data : ($webp ? $webp_url : $this->url($size, $crop));
    }

    /**
     * Получение нужной миниатюры
     *
     * @param string $size
     * @param string $crop
     *
     * @return Model|mixed|null|object|static
     */
    public function getOrCreateThumbnail($size = 'full', $crop = 'cover'){
        if(is_array($size)){
            $size = implode('_', $size);
        }
        if(empty($this->data))
            $this->updateData();
        $thumbnail = $this->images()->where('size', $size)->where('crop', $crop)->where('mime', $this->data['mime'])->first();
        if(empty($thumbnail)){
            $thumbnail = $this->createThumbnail($size, $crop);
        }

        return $thumbnail;
    }

    /**
     * Создание миниатюры изображения
     *
     * @param string $size
     * @param string $crop
     *
     * @return mixed
     */
    public function createThumbnail($size = 'full', $crop = 'cover'){
        $image = $this->images()->where('size', 'full')->first();
        if(empty($image)){
            $image = Image::find($this->createImage());
            $this->updateData();
        }

        if(env('APP_DEBUG')){
            return $image->createThumbnail($size, $crop);
        }else{
            GenerateThumbnailJob::dispatch($image, $size, $crop);
        }

        return $image;
    }

    /**
     * Выделение имени файла из пути
     *
     * @return array|mixed|string|string[]
     */
    public function getNameAttribute(){
        return str_replace(['uploads\\', 'uploads/'], '', $this->path);
    }

    /**
     * Вывод адаптивного изображения
     *
     * @param string $size
     * @param array $attributes
     * @param string $method
     * @param array $responsive
     *
     * @return string
     * @throws \Throwable
     */
    public function image($size = 'full', $attributes = [], $method = 'cover', $responsive = []){
        if(empty($this->data['width']) || empty($this->data['height']))
            $this->updateData();

        $default_size = $size;
        $max_width = $this->data['width'];
        $max_height = $this->data['height'];
        $needle_sizes = [$this->getSize($size == 'full' ? $max_width : $size[0], 1, $size, $max_width, $max_height, $method)];
        $media = [];

        if(is_array($size) && $size[0] <= $this->max_width / 2 && $size[0] < $max_width){
            if($size[0] * 2 <= $max_width)
                $needle_sizes[] = $this->getSize($size == 'full' ? $max_width : $size[0], 2, $size, $max_width, $max_height, $method);
            else
                $needle_sizes[] = $this->getSize($size == 'full' ? $max_width : $size[0], 1, $size, $max_width, $max_height, $method);
        }

        if(!empty($responsive)){
            foreach($responsive as $m => $width){
                if(is_int($width)) {
                    $w = $width;
                    $width .= 'px';
                }elseif(substr($width, -2, 2) == 'px'){
                    $w = (int)rtrim($width, 'px');
                }elseif(substr($width, -2, 2) == 'vw'){
                    $w = $this->cssCalcPart($width, $m);
                }elseif(substr($width, 0, 4) == 'calc'){
                    $calc = explode(' ', rtrim(ltrim($width, 'calc('),')'));
                    $w = 0;
                    for($i = 0; $i < count($calc); $i+=2){
                        if($i == 0 || $calc[$i - 1] == '+'){
                            $w += $this->cssCalcPart($calc[$i], $m);
                        }elseif($calc[1] == '-'){
                            $w -= $this->cssCalcPart($calc[$i], $m);
                        }
                    }
                }

                $w = ceil($w);

                if(substr($m, 0, 1) == '<'){
                    $media[] = '(max-width: '.substr($m, 1).'px) '.$width;
                    for($i=1; $i<=4; $i++){
                        $needle_sizes[] = $this->getSize($w, $i, $size, $max_width, $max_height, $method);
                    }
                }elseif(substr($m, 0, 1) == '>'){
                    $media[] = '(min-width: '.substr($m, 1).'px) '.$width;
                    for($i=1; $i<=($m <= 740 ? 4 : 2); $i++){
                        $needle_sizes[] = $this->getSize($w, $i, $size, $max_width, $max_height, $method);
                    }
                }elseif(is_int($m)){
                    $media[] = $width;
                    $needle_sizes[] = $this->getSize($w, 1, $size, $max_width, $max_height, $method);
                }
            }
        }

        $widths = [];
        foreach($needle_sizes as $size){
            if($size[0] <= $max_width)
                $widths[$size[0]] = $size;
        }

        if(empty($widths)){
            $widths = $needle_sizes;
            Log::info('Original image size too small:', ['file' => $this->getAttributes()]);
        }

        krsort($widths);
        $needle_sizes = [];
        foreach($widths as $width => $size){
            // Уменьшаем кол-во миниатюр с допуском размера в 25%
            if(empty($needle_sizes) || array_key_last($needle_sizes) >= $width * 1.25){
                $needle_sizes[$width] = $size;
            }
        }
        ksort($needle_sizes);

        $attributes['sizes'] = implode(', ', $media);

        $accepted_types = Helper::acceptedTypes();
        $images = [];
        $default_url = null;

        foreach($needle_sizes as $width => $size){
            $url = null;
            if(in_array('avif', $accepted_types)) $url = $this->url_avif($size, $method);
            if(empty($url) && in_array('webp', $accepted_types)) $url = $this->url_webp($size, $method);
            if(empty($url)) $url = $this->url($size, $method);

            if(!empty($url)){
                if($size == $default_size || is_null($default_url)){
                    $default_url = $url;
                }
                $images[$width] = $url;
            }
        }

        if(empty($default_url)){
            if(in_array('avif', $accepted_types)) $default_url = $this->url_avif($default_size, $method);
            elseif(in_array('webp', $accepted_types)) $default_url = $this->url_webp($default_size, $method);
            else $default_url = $this->url($default_size, $method);
        }

        $attributes['src'] = $default_url ?: '/images/larchik/no_image.jpg';

        $srcset_parts = [];
        foreach($images as $w => $url){
            $srcset_parts[] = $url.' '.$w.'w';
        }
        $attributes['srcset'] = implode(', ', $srcset_parts);

        if (empty($attributes['sizes'])) {
            $attributes['sizes'] = '100vw';
        }

        return view('public.layouts.image')
            ->with('attributes', $attributes)
            ->render();
    }

    /**
     * Калькуляция размера на основе css
     *
     * @param $param
     * @param $media
     * @return float|int
     */
    private function cssCalcPart($param, $media){
        $px = 0;
        if(substr($param, -2, 2) == 'vw'){
            $vw = (int)substr($param, 0, -2);

            if(!empty($vw))
                $vw = $vw / 100;

            if(is_int($media)){
                $px = $this->max_width * $vw;
            }else{
                $px = (int)substr($media, 1) * $vw;
            }
        }elseif(substr($param, -2, 2) == 'px'){
            $px = (int)substr($param, 0, -2);
        }

        return $px;
    }

    /**
     * Получение размера миниатюры
     *
     * @param $width
     * @param $ratio
     * @param $size
     * @param $original_width
     * @param $original_height
     * @param $method
     * @return array
     */
    private function getSize($width, $ratio, $size, $original_width, $original_height, $method){
        // 1. Расчет целевой ширины (с учетом DPR)
        $w = ceil($width * $ratio);
        $h = null; // Обнуляем h

        // 2. Определение целевого соотношения сторон (Aspect Ratio, AR)
        $ar = null;
        if($size == 'full') {
            // AR берется от исходного изображения
            $ar = $original_width / $original_height;
        } elseif(is_array($size)) {
            // AR берется из заданного размера [W, H]
            $ar = $size[0] / $size[1];
        }

        // Если AR не удалось определить или не требуется (например, для режима crop)
        if(empty($ar)){
            // Если $h не задана, а $w задана, может быть проблема, но продолжим.
            if (is_array($size) && isset($size[1])) {
                $h = $size[1]; // Берем высоту из массива, если она есть
            } else {
                // Попытка использовать AR исходника как запасной вариант
                $ar = $original_width / $original_height;
            }
        }

        // 3. Расчет целевой высоты h, если она не задана (по пропорциям)
        if(!empty($ar)){
            $h = ceil($w / $ar);
        }

        // Вычисляем соотношение W/H для текущих $w и $h (целевое AR)
        $target_ar = $w / $h;
        // Вычисляем соотношение W/H исходника
        $original_ar = $original_width / $original_height;

        // 4. Логика Contain/Cover (принудительное изменение W или H)

        // Если $original_ar > $target_ar: Исходное изображение относительно ШИРЕ цели
        if ($original_ar > $target_ar) {
            switch($method){
                case 'contain':
                    // Вписать: Привязываемся к ВЫСОТЕ, чтобы гарантировать, что W не превысит цель.
                    // Пересчитываем W исходя из H и оригинальных пропорций.
                    $w = ceil($h * $original_ar);
                    break; // <-- КРИТИЧНОЕ ИСПРАВЛЕНИЕ: ДОБАВЛЕН break
                case 'cover':
                    // Заполнить/Обрезать: Привязываемся к ШИРИНЕ, чтобы гарантировать, что W заполнит цель.
                    // Пересчитываем H исходя из W и оригинальных пропорций.
                    $h = ceil($w / $original_ar);
                    break; // <-- КРИТИЧНОЕ ИСПРАВЛЕНИЕ: ДОБАВЛЕН break
            }
        }
        // Если $original_ar < $target_ar: Исходное изображение относительно ВЫШЕ цели
        elseif ($original_ar < $target_ar) {
            switch($method){
                case 'contain':
                    // Вписать: Привязываемся к ШИРИНЕ, чтобы гарантировать, что H не превысит цель.
                    // Пересчитываем H исходя из W и оригинальных пропорций.
                    $h = ceil($w / $original_ar);
                    break; // <-- КРИТИЧНОЕ ИСПРАВЛЕНИЕ: ДОБАВЛЕН break
                case 'cover':
                    // Заполнить/Обрезать: Привязываемся к ВЫСОТЕ, чтобы гарантировать, что H заполнит цель.
                    // Пересчитываем W исходя из H и оригинальных пропорций.
                    $w = ceil($h * $original_ar);
                    break; // <-- КРИТИЧНОЕ ИСПРАВЛЕНИЕ: ДОБАВЛЕН break
            }
        }
        // Если $original_ar == $target_ar: Пропорции совпадают, ничего не делаем.

        // 5. Возврат результата
        return [$w, $h];
    }

    /**
     * Используется ли изображение
     * @param int $id Id изображения
     * @return bool
     */
    public function is_used($id)
    {
        $blog = Blog::where('image_id', $id)
            ->take(1)
            ->count();
        if($blog > 0)
            return true;

        $products = Product::where('image_id', $id)
            ->take(1)
            ->count();
        if($products > 0)
            return true;

        return false;
    }

    /**
     * Вывод оптимизированного изображения
     *
     * @param string $size
     * @param array $attributes
     * @param bool $lazy
     * @param string $crop
     * @param array $responsive
     * @return string
     * @throws \Throwable
     */
    public function optimized($size = 'full', $attributes = [], $lazy = false, $crop = 'cover', $responsive = []){
        $accepted_types = Helper::acceptedTypes();
        if(in_array('avif', $accepted_types)){
            $thumbnail = $this->getOrCreateThumbnail($size, $crop);
            $pixels = $thumbnail->width * $thumbnail->height;

            // Выводим изображение в avif если оно больше 10000px или не поддерживается webp
            if(!empty($pixels) && $pixels > 10000 || !in_array('webp', $accepted_types)){
                return $this->getAvif('html', $size, $crop, $attributes, $lazy);
            }
        }

        if(in_array('webp', $accepted_types)){
            return $this->getWebp('html', $size, $crop, $attributes, $lazy);
        }else{
            return $this->getOriginal('html', $size, $crop, $attributes, $lazy);
        }
    }
}
