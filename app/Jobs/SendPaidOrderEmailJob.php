<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;

class SendPaidOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order_id;
    protected $order_user;
    protected $order;

    public function __construct($order_id, $order_user, $order)
    {
        $this->order_id = $order_id;
        $this->order_user = $order_user;
        $this->order = $order;
    }

    public function handle()
    {
        // Send email to customer about paid order
        Mail::send('emails.paid', ['user' => $this->order_user, 'order' => $this->order], function($msg){
            $msg->from(env('MAIL_FROM_ADDRESS'), __('Online store').' '.env('APP_NAME'));
            $msg->to($this->order_user['email']);
            $msg->subject(__('Order paid').' №' . $this->order_id);
        });
    }
}
