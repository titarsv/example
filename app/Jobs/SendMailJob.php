<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\Sendpulse;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected $subject;
    protected $html;
    protected $from;

    /**
     * Создать новый экземпляр задания.
     *
     * @return void
     */
    public function __construct($to, $subject, $html, $from){
        $this->to = $to;
        $this->subject = $subject;
        $this->html = $html;
        $this->from = $from;
    }

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle(){
        $sendpulse = new Sendpulse();
        $sendpulse->mail(
            $this->to,
            $this->subject,
            $this->html,
            $this->from
        );
    }
}
