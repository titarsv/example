<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;

class SendOrderEmailsJob implements ShouldQueue
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
        // Send emails to admin
        $setting = new Setting();
        $emails = [];
        foreach($setting->get_setting('emails') as $email){
            if(in_array($email->destination, ['orders', 'all'])){
                $emails[] = $email->email;
            }
        }

        if(!empty($emails)){
            Mail::send('emails.order', ['user' => $this->order_user, 'order' => $this->order, 'admin' => true], function ($msg) use ($emails) {
                $msg->from('admin@' . str_replace(['http://', 'https://'], '', env('APP_URL')), __('Online store').' '.env('APP_NAME'));
                $msg->to($emails);
                $msg->subject(__('New order').' №' . $this->order_id);
            });
        }

        // Send email to customer
        Mail::send('emails.order', ['user' => $this->order_user, 'order' => $this->order, 'admin' => false], function($msg){
            $msg->from(env('MAIL_FROM_ADDRESS'), __('Online store').' '.env('APP_NAME'));
            $msg->to($this->order_user['email']);
            $msg->subject(__('New order'));
        });
    }
}
