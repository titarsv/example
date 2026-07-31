<?php

namespace Modules\Notifications\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\Client;

/**
 * Consolidates what used to be four separate copies of the same
 * "instantiate a Telegram bot client and message every moderated
 * subscriber" logic (CheckoutController, ContactFormsController,
 * ReviewsController, SiteReviewsController). Callers build their own
 * message text and just call broadcast() - token lookup, the disabled
 * module check and failure isolation all live here once.
 */
class TelegramNotifierService
{
    public function broadcast(string $text): void
    {
        if (!module_active('notifications')) {
            return;
        }

        try {
            $settings = new Setting();
            $telegram = (array) $settings->get_setting('telegram');

            if (empty($telegram['token']) || empty($telegram['clients'])) {
                return;
            }

            $bot = new Client($telegram['token']);

            foreach ($telegram['clients'] as $client) {
                if (!empty($client->moderated)) {
                    $bot->sendMessage($client->chat, $text);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed: '.$e->getMessage());
        }
    }
}
