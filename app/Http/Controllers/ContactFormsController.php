<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\Setting;

class ContactFormsController extends Controller
{
    private $domain = 'properloud.cc';


    public function sendForm(Request $request){
        $files = [];
        if(count($_FILES)){
            foreach ($_FILES as $file) {
                if ($file["error"] == 0) {
                    $tmp_name = $file["tmp_name"];
                    $name = basename($file["name"]);
                    $storage = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $name);
                    move_uploaded_file($tmp_name, $storage);
                    $files[] = array('path' => $storage, 'name' => $tmp_name);
                }
            }
        }

        if(!empty($request->data)){
            $data = json_decode($request->data);

            $form = !empty($data->form) ? $data->form->val : 'Contact form';

            \App\Models\Request::insert([
                'form' => $form,
                'name' => isset($data->name->val) ? $data->name->val : '',
                'email' => isset($data->email->val) ? $data->email->val : '',
                'comment' => isset($data->message->val) ? $data->message->val : ''
            ]);

            $settings = new Setting();
            $telegram = (array)$settings->get_setting('telegram');
            $token = env('TELEGRAM_TOKEN');
            if(!empty($token)){
                $bot = new \TelegramBot\Api\Client($token);

                $text = trans('messages.new_application', ['sitename' => env('APP_NAME')]) . "\n";
                if(isset($data->name->val))
                    $text .= trans('messages.name_label').": ".$data->name->val."\n";
                if(isset($data->email->val))
                    $text .= trans('messages.email_label').": ".$data->email->val."\n";
                if(isset($data->message->val))
                    $text .= trans('messages.message_label').": ".$data->message->val."\n";

                foreach($telegram['clients'] as $id => $client){
                    if($client->moderated){
                        $bot->sendMessage($client->chat, $text);
                    }
                }
            }

            $this->sendMail($data, $files);
        }

        return response()->json(['status' => 'success']);
    }

    public function sendMail($data, $files = []){
        $setting = new Setting();
        $domain = $this->domain;

        $eol = PHP_EOL;
        $msg = "<html><body style='font-family:Arial,sans-serif;'>";
        $msg .= "<h2 style='color:#161616;font-weight:bold;font-size:30px;border-bottom:2px dotted #bd0707;'>" . trans('locale.messages.new_request_on_website', ['domain' => $domain]) . "</h2>" . $eol;

        foreach($data as $key => $params){
            if(!empty($params->title) && !empty($params->val)){
                $val = $this->prepareData($params->val, $key);
                $msg .= "<p><strong>$params->title:</strong> $val</p>" . $eol;
            }
        }

        $msg .= "</body></html>";

        Mail::send('emails.sendmail', ['html' => $msg], function($msg) use($setting, $domain, $files){
            $msg->from('noreply@' . $domain, env('APP_NAME'));
            $emails = [];
            foreach($setting->get_setting('emails') as $email){
                if($email->destination == 'orders' || $email->destination == 'all'){
                    $emails[] = $email->email;
                }
            }
            $msg->to($emails);
            $msg->subject('Contact us');
            if(!empty($files)){
                foreach($files as $file){
                    $msg->attach($file['path'], ['as' => $file['name']]);
                }
            }
        });

       return true;
    }

    public function prepareData($data, $key){
        switch ($key) {
            case 'referer':
                return substr($data, 0, 30);
            case 'term':
                return urldecode($data);
            default:
                return $data;
        }
    }
}
