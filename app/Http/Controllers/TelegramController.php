<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\App;

class TelegramController extends Controller
{
    public function index(Setting $settings){
        $telegram = (array)$settings->get_setting('telegram');
        $token = $telegram['token'];
        $bot = new \TelegramBot\Api\Client($token);

        // команда для start
        $bot->command('start', function ($message) use ($bot, $telegram) {
            $user = $message->getFrom();
//            $user_id = $user->getId();
            $name = trim($user->getFirstName().' '.$user->getLastName());
            if(!empty($name)){
                $answer = trans('locale.telegram.welcome_named', ['name' => $name]);
            }else{
                $answer = trans('locale.telegram.welcome');
            }

//            if(!empty($telegram['clients']) && isset($telegram['clients'][$user_id])) {
//                $bot->sendMessage($message->getChat()->getId(), $answer);
//            }else{
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => trans('locale.telegram.subscribe_button'), 'request_contact' => true]]], true, true);
            $bot->sendMessage($message->getChat()->getId(), $answer, null, false, null, $keyboard);
//            }
        });

        // команда для помощи
        $bot->command('help', function ($message) use ($bot) {
            $answer = trans('locale.telegram.help_commands', [
                'help_command' => '/help',
                'help_description' => trans('locale.telegram.help_description')
            ]);
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot, $telegram, $settings) {
            $message = $update->getMessage();
            $contact = $message->getContact();
            $user = $message->getFrom();
            $user_id = $user->getId();
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardRemove();

            if(!empty($contact))
                $phone = $contact->getPhoneNumber();

            if(!empty($phone)){
                if(empty($telegram['clients']) || !isset($telegram['clients']->$user_id)){
                    $telegram['clients']->$user_id = [
                        'name' => trim($user->getFirstName().' '.$user->getLastName()),
                        'phone' => $contact->getPhoneNumber(),
                        'chat' => $message->getChat()->getId(),
                        'moderated' => false
                    ];
                    $settings->update_setting('telegram', $telegram);

                    $bot->sendMessage($message->getChat()->getId(), trans('locale.telegram.phone_sent_for_moderation', ['phone' => $contact->getPhoneNumber()]), null, false, null, $keyboard);
                }elseif(isset($telegram['clients']->$user_id)){
                    $bot->sendMessage($message->getChat()->getId(), trans('locale.telegram.already_subscribed'), null, false, null, $keyboard);
                }
            }
        }, function () {
            return true;
        });

        $bot->run();
    }
}
