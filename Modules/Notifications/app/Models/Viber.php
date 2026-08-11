<?php

namespace Modules\Notifications\Models;

class Viber
{
    private $api_key = null;
    private $send_name = "Exvelo";
    private $is_log = true;

    public function __construct()
    {
        $this->api_key = env('VIBER_TOKEN', null);
    }

    public function setWebHook(){
        $data = [
            'auth_token' => $this->api_key,
            'url' => env('APP_URL').'/api/viber',
            'event_types' => [
                'subscribed',
                'unsubscribed',
                'delivered',
                'message',
                'seen'
            ]
        ];

        $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if($err){
            return $err;
        }else{
            return $response;
        }
    }

    public function getMessage(){
        $request = file_get_contents("php://input");
        $input = json_decode($request, true);
        $this->put_log_in($request);

        if($input['event'] == 'webhook') {
            $webhook_response['status'] = 0;
            $webhook_response['status_message'] = "ok";
            $webhook_response['event_types'] = 'delivered';

            return response()->json($webhook_response);
        }else{
            $sender_id = $input['sender']['id']; //unique viber id of user who sent the message
            $sender_name = $input['sender']['name']; //name of the user who sent the message

            if($input['event'] == "subscribed"){
                $result = $this->sendMsgText($sender_id, "Спасибо, что подписались на нас!");
            }else if($input['event'] == "conversation_started"){
                $result = $this->sendMsgText($sender_id, "Беседа началась!");
            }elseif($input['event'] == "message"){
                $type = $input['message']['type']; //type of message received (text/picture)
                $text = $input['message']['text']; //actual message the user has sent
                $result = $this->sendMsg($sender_id, $text, $type);
            }

            if(!empty($result)){
                return response()->json($result);
            }
        }
    }

    public function put_log_in($data){
        if($this->is_log){
            file_put_contents("tmp_in.txt", $data."\n", FILE_APPEND);
        }
    }

    public function put_log_out($data){
        if($this->is_log){
            file_put_contents("tmp_out.txt", $data."\n", FILE_APPEND);
        }
    }

    public function sendReq($data){
        $request_data = json_encode($data);
        $this->put_log_out($request_data);

        //here goes the curl to send data to user
        $ch = curl_init("https://chatapi.viber.com/pa/send_message");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if($err){
            return $err;
        }else{
            return $response;
        }
    }

    public function sendMsg($sender_id, $text, $type, $tracking_data = Null, $arr_asoc = Null){
        $data['auth_token'] = $this->api_key;
        $data['receiver'] = $sender_id;
        if($text != Null) {$data['text'] = $text;}
        $data['type'] = $type;
        //$data['min_api_version'] = $input['sender']['api_version'];
        $data['sender']['name'] = $this->send_name;
        //$data['sender']['avatar'] = $input['sender']['avatar'];
        if($tracking_data != Null) {$data['tracking_data'] = $tracking_data;}
        if($arr_asoc != Null){
            foreach($arr_asoc as $key => $val) {$data[$key] = $val;}
        }

        return $this->sendReq($data);
    }

    public function sendMsgText($sender_id, $text, $tracking_data = Null){
        return $this->sendMsg($sender_id, $text, "text", $tracking_data);
    }
}