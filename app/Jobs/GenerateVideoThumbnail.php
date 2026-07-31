<?php

namespace App\Jobs;

use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;

    public function __construct($file_id)
    {
        $this->file = File::find($file_id);
    }

    public function handle()
    {
        $ffmpegPath = config('services.ffmpeg.ffmpeg_path');
        $ffprobePath = config('services.ffmpeg.ffprobe_path');

        if (!$this->is_command_exists($ffmpegPath)) {
            Log::error("FFMpeg не найден по пути: $ffmpegPath");
            $this->fail(new \Exception("FFMpeg binary not found."));
            return;
        }

        // Получаем полные пути к файлам
        $fullVideoPath = public_path($this->file->path);
        $fullThumbPath = public_path('thumbnails/'.$this->file->title.'.jpg');

        // Инициализация FFMpeg с настройками из конфига
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => $ffmpegPath,
            'ffprobe.binaries' => $ffprobePath,
            'timeout'          => 3600,
        ]);

        $video = $ffmpeg->open($fullVideoPath);

        $video->frame(TimeCode::fromSeconds(2))
            ->save($fullThumbPath);

        $this->file->data = $this->file->getFullData();
        $this->file->save();
    }

    private function is_command_exists($path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Для Windows: если путь абсолютный, проверяем file_exists
            return file_exists($path) || shell_exec("where $path");
        }
        // Для Linux: проверяем через which
        return (bool) shell_exec("which " . escapeshellarg($path));
    }
}
