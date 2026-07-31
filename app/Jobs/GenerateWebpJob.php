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

class GenerateWebpJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Экземпляр файла.
     *
     * @var \App\Models\Image
     */
    protected $image;

    /**
     * Создать новый экземпляр задания.
     *
     * @return void
     */
    public function __construct(Image $image){
        $this->image = $image;
    }

    /**
     * Уникальный идентификатор задания.
     *
     * @return string
     */
    public function uniqueId(){
        return $this->image->id;
    }

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle(){
        $webp = Image::where('file_id', $this->image->file_id)->where('size', $this->image->size)->where('crop', $this->image->crop)->where('mime', 'image/webp')->first();
        if(empty($webp)){
            $webp = $this->image->createWebp();
            if(!empty($thumbnail)){
                $file = $this->image->file;
                $data = $file->data;
                $data['sizes'][$this->image->size][$this->image->crop]['webp'] = '/'.ltrim(str_replace('\\', '/', $webp->path), '/');
                $file->data = $data;
                $file->save();

                Log::info('File data was updated:', ['size' => $this->image->size, 'crop' => $this->image->crop, 'data' => $data]);
            }
            Log::info('New webp was created:', ['source' => $this->image->getAttributes(), 'webp' => !empty($webp) ? $webp->getAttributes() : null]);
        }else{
            Log::info('Webp already exists:', ['webp' => $webp->getAttributes()]);
        }
    }
}
