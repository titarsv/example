<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Image;

class GenerateThumbnailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Экземпляр файла.
     *
     * @var \App\Models\Image
     */
    protected $image;
    protected $size;
    protected $crop;

    /**
     * Создать новый экземпляр задания.
     *
     * @param Image $image
     * @param $size
     * @param $crop
     *
     * @return void
     */
    public function __construct(Image $image, $size, $crop){
        $this->image = $image;
        $this->size = $size;
        $this->crop = $crop;
    }

    /**
     * Уникальный идентификатор задания.
     *
     * @return string
     */
    public function uniqueId(){
        return md5(json_encode([$this->image->id, $this->size, $this->crop], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle(){
        $thumbnail = Image::where('file_id', $this->image->file_id)->where('size', $this->size)->where('crop', $this->crop)->where('mime', $this->image->mime)->first();
        if(empty($thumbnail)){
            $thumbnail = $this->image->createThumbnail($this->size, $this->crop);
            if(!empty($thumbnail)){
                $file = $this->image->file;
                $data = $file->data;
                $data['sizes'][$this->size]['url'] = '/'.ltrim(str_replace('\\', '/', $thumbnail->path), '/');
                $data['sizes'][$this->size]['width'] = $thumbnail->width;
                $data['sizes'][$this->size]['height'] = $thumbnail->height;
                $data['sizes'][$this->size][$this->crop]['url'] = '/'.ltrim(str_replace('\\', '/', $thumbnail->path), '/');
                $file->data = $data;
                $file->save();

                Log::info('File data was updated:', ['size' => $this->size, 'crop' => $this->crop, 'data' => $data]);
            }
            Log::info('New thumbnail was created:', ['source' => $this->image->getAttributes(), 'thumbnail' => $thumbnail->getAttributes()]);
        }else{
            Log::info('Thumbnail already exists:', ['thumbnail' => $thumbnail->getAttributes()]);
        }
    }
}
