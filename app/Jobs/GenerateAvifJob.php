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

class GenerateAvifJob implements ShouldQueue, ShouldBeUnique
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
        if(!$this->image){
            return;
        }

        $avif = $this->image->createAvif();
        if(!empty($avif)){
            Log::info('New webp was created:', ['source' => $this->image->getAttributes(), 'avif' => $avif]);
        }
    }
}
