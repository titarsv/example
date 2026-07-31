<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class UpdateBoughtTogetherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productIds;

    public function __construct(array $productIds)
    {
        $this->productIds = array_values(array_unique(array_map('intval', $productIds)));
    }

    public function handle()
    {
        $count = count($this->productIds);
        if ($count < 2) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $this->productIds[$i];
                $b = $this->productIds[$j];

                Redis::command('zincrby', ["bought_with_{$a}", 1, $b]);
                Redis::command('zincrby', ["bought_with_{$b}", 1, $a]);
            }
        }
    }
}