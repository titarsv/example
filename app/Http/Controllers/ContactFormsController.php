<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\Setting;
use Modules\Notifications\Services\TelegramNotifierService;

class ContactFormsController extends Controller
{
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

        $data = [
            'form' => $request->input('form', 'Contact form'),
            'name' => $request->input('name', ''),
            'email' => $request->input('email', ''),
            'message' => $request->input('message', ''),
        ];

        if(!empty($data['name']) || !empty($data['email']) || !empty($data['message'])){
            \App\Models\Request::insert([
                'form' => $data['form'],
                'name' => $data['name'],
                'email' => $data['email'],
                'comment' => $data['message']
            ]);

            $text = trans('messages.new_application', ['sitename' => env('APP_NAME')]) . "\n";
            if(!empty($data['name']))
                $text .= trans('messages.name_label').": ".$data['name']."\n";
            if(!empty($data['email']))
                $text .= trans('messages.email_label').": ".$data['email']."\n";
            if(!empty($data['message']))
                $text .= trans('messages.message_label').": ".$data['message']."\n";

            app(TelegramNotifierService::class)->broadcast($text);

            $this->sendMail($data, $files);
        }

        return response()->json(['status' => 'success']);
    }

    public function sendMail($data, $files = []){
        $setting = new Setting();
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url');

        $eol = PHP_EOL;
        $msg = "<html><body style='font-family:Arial,sans-serif;'>";
        $msg .= "<h2 style='color:#161616;font-weight:bold;font-size:30px;border-bottom:2px dotted #bd0707;'>" . trans('locale.messages.new_request_on_website', ['domain' => $domain]) . "</h2>" . $eol;

        $labels = [
            'name' => trans('messages.name_label'),
            'email' => trans('messages.email_label'),
            'message' => trans('messages.message_label'),
        ];

        foreach($labels as $key => $title){
            if(!empty($data[$key])){
                $val = $this->prepareData($data[$key], $key);
                $msg .= "<p><strong>$title:</strong> $val</p>" . $eol;
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
