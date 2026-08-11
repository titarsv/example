<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Setting;
use Modules\Delivery\Models\Newpost;
use Modules\Delivery\Models\Justin;

class SettingsController extends Controller
{
	private $user;

	function __construct(){
		$this->user = Sentinel::check();
	}

    /**
     * Страница настроек магазина
     *
     * @param Setting $setting
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminShopSettingsAction(Setting $setting){
//        $update_period = [
//            (object)['name' => 'Не выбрано', 'value' => 0],
//            (object)['name' => 'Каждый день', 'value' => 86400],
//            (object)['name' => 'Раз в неделю', 'value' => 604800],
//            (object)['name' => 'Раз в месяц', 'value' => 2592000],
//            (object)['name' => 'Раз в полгода', 'value' => 15552000],
//        ];
//
//        $currencies = ['USD', 'EUR', 'RUB', 'UAH', 'BYN', 'KZT'];
//
//        $newpost = new Newpost();
//        $data = $newpost->getCounterparties('', 'Sender');
//        $np_senders = !empty($data) ? $data['data'] : [];
//
//        $settings = $setting->get_extra();
//
//        $regions = $newpost->getRegions();
//        $region_id = null;
//        $cities = [];
//        $city_id = isset($settings->newpost_sender_city_id) ? $settings->newpost_sender_city_id : null;
//        $warehouses = [];
//        $warehouse_id = isset($settings->newpost_warehouse_sender_id) ? $settings->newpost_warehouse_sender_id : null;
//
//        if(!empty($city_id)){
//            $city = $newpost->getCityByCid($city_id);
//
//            if(!empty($city)){
//                $region_id = $city->region_id;
//                $cities = $newpost->getCities($region_id);
//                $city_id = $city->city_id;
//                $warehouses = $newpost->getWarehouses($city_id);
//            }
//        }
//
//        $phone = isset($settings->newpost_sender_contact_phone) ? $settings->newpost_sender_contact_phone : null;
//
//        $justin = new Justin();
//        $justin_region_id = $setting->get_setting('justin_sender_region_id');
//        $justin_city_id = $setting->get_setting('justin_sender_city_id');
//        $justin_warehouse_id = $setting->get_setting('justin_sender_warehouse_id');
//
//        $justin_regions = $justin->getRegions();
//        $justin_cities = !empty($justin_region_id) ? $justin->getCities($justin_region_id) : [];
//        $justin_warehouses  = !empty($justin_city_id) ? $justin->getWarehouses($justin_city_id) : [];

        $data = [
            'user' => $this->user,
            'settings' => $setting->get_all(),
//            'update_period' => $update_period,
//            'currencies' => $currencies,
//            'np_senders' => $np_senders,
//            'regions' => $regions,
//            'cities' => $cities,
//            'warehouses' => $warehouses,
//            'region_id' => $region_id,
//            'city_id' => $city_id,
//            'warehouse_id' => $warehouse_id,
//            'phone' => $phone,
//            'justin_regions' => $justin_regions,
//            'justin_cities' => $justin_cities,
//            'justin_warehouses' => $justin_warehouses,
//            'justin_region_id' => $justin_region_id,
//            'justin_city_id' => $justin_city_id,
//            'justin_warehouse_id' => $justin_warehouse_id,
            'justin_phone' => $setting->get_setting('justin_sender_phone'),
            'justin_company' => $setting->get_setting('justin_sender_company'),
            'languages' => Config::get('app.locales_names')
        ];

        if(isset($data['settings']->payment_methods) && in_array('mycryptocheckout', $data['settings']->payment_methods)){
            $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
            $data['mycryptocheckout_data'] = $this->get_mcc_account_data();
            $data['mycryptocheckout_wallets'] = $mcc->wallets();
        }

        $data['maintenanceSettings'] = $this->getMaintenanceSettings();

        return view('admin.shop.settings', $data);
    }

    private function getMaintenanceSettings()
    {
        $defaults = [
            'message' => 'We are currently performing scheduled maintenance. Please check back soon.',
            'retry' => 60
        ];

        $settings = Setting::where('key', 'maintenance_settings')->first();

        if ($settings) {
            $savedSettings = json_decode($settings->value, true);
            return array_merge($defaults, $savedSettings['maintenance'] ?? []);
        }

        return $defaults;
    }

    /**
     * Save MyCryptoCheckout settings
     */
    public function adminSaveMyCryptoCheckoutSettingsAction(Request $request, Setting $setting)
    {
        $data = $request->all();

        // Validate the input
        $validator = Validator::make($data, [
            'action' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();

        if($request->action == 'retrieve_account'){
            $mcc->account()->retrieve();
        }elseif($request->action == 'delete_account'){
            $mcc->account()->delete();
        }

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.settings.mycryptocheckout_settings_saved')
        ]);
    }

    public function get_mcc_account_data()
    {
        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
        $mcc_account_data = $mcc->account()->data;

        $timezone = new \DateTimeZone('Europe/London');
        $dateTime = new \DateTime('now', $timezone);
        $offsetInSeconds = $timezone->getOffset($dateTime); // Получить смещение в секундах
        $offsetInHours = $offsetInSeconds / 3600; // Преобразовать в часы

        $data = [];
        $data['API key'] = $mcc_account_data->domain_key ?? '';
        $data['Server name'] = config('app.url');
        $data['Account data refreshed'] = isset($mcc_account_data->updated) ? sprintf( '<span title="%s">%s</span>',
            $this->local_date( $mcc_account_data->updated ) . ' ' . $this->local_time( $mcc_account_data->updated ),
            sprintf( '%s ago' , $this->human_time_diff( $mcc_account_data->updated ) )
        ) : '';
        if(!empty((array)$mcc_account_data) && $mcc->account()->has_license()){
            $time = $mcc->account()->get_license_valid_until();
            $data['Your license expires'] =  sprintf( '%s (%s)',
                $this->local_date( $time ),
                $this->human_time_diff( $time )
            );
        }
        $url = 'https://mycryptocheckout.com/pricing/?domain='.base64_encode($mcc->get_client_url());
        $text = !empty((array)$mcc_account_data) && $mcc->account()->has_license() ? 'Extend my license' : 'Add an unlimited license to my cart';
        $url = sprintf( '<a href="%s">%s</a> &rArr;',
            $url,
            $text
        );
        if(!empty((array)$mcc_account_data) && $mcc->account()->has_license()){
            $data['Extend your license'] = $url;
        }else{
            $data['Purchase a license for unlimited payments'] = $url;
        }
        $data['Payments remaining this month'] = !empty((array)$mcc_account_data) ? $mcc->account()->get_payments_left_text() : '';
        $data['Payments processed'] = !empty((array)$mcc_account_data) ? $mcc->account()->get_payments_used() : '';
        $data['Physical currency exchange rates updated'] = isset($mcc_account_data->physical_exchange_rates) ? sprintf( '<span title="%s">%s</span>',
            $this->local_date( $mcc_account_data->physical_exchange_rates->timestamp ) . ' ' . $this->local_time( $mcc_account_data->physical_exchange_rates->timestamp ),
            sprintf( '%s ago' , $this->human_time_diff( $mcc_account_data->physical_exchange_rates->timestamp ) )
        ) : '';
        $data['Cryptocurrency exchange rates updated'] = isset($mcc_account_data->virtual_exchange_rates) ? sprintf( '<span title="%s">%s</span>',
            $this->local_date( $mcc_account_data->virtual_exchange_rates->timestamp ) . ' ' . $this->local_time( $mcc_account_data->virtual_exchange_rates->timestamp ),
            sprintf( '%s ago' , $this->human_time_diff( $mcc_account_data->virtual_exchange_rates->timestamp ) )
        ) : '';

        $wallets = $mcc->wallets();
        if(count($wallets) > 0){
            $currencies = $mcc->currencies();
            $exchange_rates = [];
            foreach($wallets as $index => $wallet) {
                $id = $wallet->currency_id;
                if(isset( $exchange_rates[$id]))
                    continue;
                $currency = $currencies->get($id);
                if($currency)
                    $exchange_rates[$id] = sprintf( '1 USD = %s %s', $currency->convert( 'USD', 1 ), $id );
                else
                    $exchange_rates[$id] = sprintf( 'Currency %s is no longer available!', $id );
            }
            ksort( $exchange_rates );
            $exchange_rates = implode( "\n", $exchange_rates );
            $exchange_rates = $this->wpautop( $exchange_rates );
        }
        else
            $exchange_rates = 'n/a';

        $data['Exchange rates for your currencies'] = $exchange_rates;
        if(isset($mcc_account_data->payment_amounts) && count((array)$mcc_account_data->payment_amounts) > 0){
            $text = '';
            $payment_amounts = (array) $mcc_account_data->payment_amounts;
            ksort( $payment_amounts );
            foreach( $payment_amounts as $currency_id => $amounts )
            {
                $amounts = (array)$amounts;
                ksort( $amounts );
                $amounts = implode( ', ', array_keys( $amounts ) );
                $text .= sprintf( '<p>%s: %s</p>', $currency_id, $amounts );
            }
            $data['Reserved amounts'] = $text;
            $data['Next scheduled hourly cron'] = date( 'Y-m-d H:i:s', strtotime(date('Y-m-d H').':00:00') + 3600 + $offsetInHours);
            $data['Next scheduled account data update'] = date( 'Y-m-d H:i:s', strtotime(date('Y-m-d H').':00:00') + 3600 + $offsetInHours);
        }


//            dd($mcc_account_data);
        return $data;
    }

    /**
     * Сохранение настроек доставки
     *
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminSaveDeliveryShopSettingsAction(Request $request, Setting $settings){
//        $rules = [
//            'newpost_api_key' => 'filled|max:32',
//            'newpost_regions_update_period' => 'filled|not_in:0',
//            'newpost_cities_update_period' => 'filled|not_in:0',
//            'newpost_warehouses_update_period' => 'filled|not_in:0'
//        ];
//
//        $messages = [
//            'newpost_api_key.filled' => 'Поле должно быть заполнено!',
//            'newpost_api_key.max' => 'Длина ключа должна быть не более 32 символов!',
//            'newpost_regions_update_period.filled' => 'Выберите период!',
//            'newpost_regions_update_period.not_in' => 'Выберите период!',
//            'newpost_cities_update_period.filled' => 'Выберите период!',
//            'newpost_cities_update_period.not_in' => 'Выберите период!',
//            'newpost_warehouses_update_period.filled' => 'Выберите период!',
//            'newpost_warehouses_update_period.not_in' => 'Выберите период!'
//        ];
//
//        $validator = Validator::make($request->all(), $rules, $messages);
//        if($validator->fails()){
//            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
//        }

        $settings->update_settings($request->except('_token'), false);
        Cache::flush();

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.settings.delivery_settings_saved')
        ], 200);
    }

    /**
     * Сохранение настроек оплаты
     *
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminSavePaymentShopSettingsAction(Request $request, Setting $settings)
    {
        if(!empty($request->payment_methods)){
            $rules = [];
            $messages = [
                'liqpay_api_public_key.filled' => trans('locale.settings.validation.public_key_required'),
                'liqpay_api_private_key.filled' => trans('locale.settings.validation.private_key_required'),
                'liqpay_api_currency.filled' => trans('locale.settings.validation.currency_required'),
                'liqpay_api_currency.not_in' => trans('locale.settings.validation.currency_required'),
                'wayforpay_account.filled' => trans('locale.settings.validation.account_required'),
                'wayforpay_secret.filled' => trans('locale.settings.validation.secret_required'),
            ];

            if(in_array('liqpay', $request->payment_methods)){
                $rules['liqpay_api_public_key'] = 'filled';
                $rules['liqpay_api_private_key'] = 'filled';
                $rules['liqpay_api_currency'] = 'filled|not_in:0';

                foreach(Config::get('app.locales_names') as $lang => $name){
                    $rules['payment_liqpay_name_'.$lang] = 'filled';
                    $messages['payment_liqpay_name_'.$lang.'.filled'] = trans('locale.settings.validation.payment_name_required');
                }
            }

            if(in_array('wayforpay', $request->payment_methods)){
                $rules['wayforpay_account'] = 'filled';
                $rules['wayforpay_secret'] = 'filled';

                foreach(Config::get('app.locales_names') as $lang => $name){
                    $rules['payment_wayforpay_name_'.$lang] = 'filled';
                    $messages['payment_wayforpay_name_'.$lang.'.filled'] = trans('locale.settings.validation.payment_name_required');
                }
            }

            $validator = Validator::make($request->all(), $rules, $messages);
            if($validator->fails()){
                return response()->json([
                    'result' => 'error',
                    'message' => trans('locale.settings.messages.validation_error'),
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        $settings->update_settings($request->except('_token'), false);
        Cache::flush();

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.settings.payment_settings_saved')
        ], 200);
    }

    /**
     * Сохранение контактов
     *
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminSaveContactsShopSettingsAction(Request $request, Setting $settings){
        $rules = [
            'emails.*.email' => 'email|distinct|filled',
//            'phones.*.phone' => 'distinct|filled|regex:/^[0-9\-! ,\'\"\/+@\.:\(\)]+$/',
        ];

        $messages = [
            'emails.*.email' => trans('locale.settings.validation.email_invalid'),
            'emails.*.distinct' => trans('locale.settings.validation.field_distinct'),
            'emails.*.filled' => trans('locale.settings.validation.field_required'),
//            'phones.*.distinct' => trans('locale.settings.validation.field_distinct'),
//            'phones.*.filled' => trans('locale.settings.validation.field_required'),
//            'phones.*.regex' => trans('locale.settings.validation.phone_invalid'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $data = $request->only(['phones', 'emails']);
        if(empty($data['phones']))
            $data['phones'] = [];
        if(empty($data['emails']))
            $data['emails'] = [];

        $settings->update_settings($data, true);

        return response()->json(['result' => 'success', 'message' => trans('locale.settings.contacts_saved')], 200);
    }

//	public function newpostUpdate(Newpost $newpost){
//		$result = $newpost->updateAll();
//
//		if ($result){
//			$message_status = 'message-success';
//			$message_text = 'Данные API Новой Почты успешно обновлены!';
//		} else {
//			$message_status = 'message-error';
//			$message_text = 'При обновлении данных произошла ошибка! Подробности: /storage/logs/laravel.log';
//		}
//		return redirect('/admin/delivery-and-payment')
//			->with($message_status, $message_text);
//	}

    public function adminSaveTrustpilotSettingsAction(Request $request, Setting $settings){
        $data = $request->only(['trustpilot_rating', 'trustpilot_rating_name', 'trustpilot_total_reviews', 'trustpilot_trustpilot_link']);

        $settings->update_settings($data, true);

        return response()->json(['result' => 'success', 'message' => trans('locale.settings.trustpilot_saved')], 200);
    }

    private function human_time_diff( $from, $to = 0 ) {
        if ( empty( $to ) ) {
            $to = time();
        }

        $diff = (int) abs( $to - $from );

        if ( $diff < 60 ) {
            $secs = $diff;
            if ( $secs <= 1 ) {
                $secs = 1;
            }
            $since = sprintf( '%s seconds', $secs );
        } elseif ( $diff < 3600 && $diff >= 60 ) {
            $mins = round( $diff / 60 );
            if ( $mins <= 1 ) {
                $mins = 1;
            }
            $since = sprintf( '%s minutes', $mins );
        } elseif ( $diff < 86400 && $diff >= 3600 ) {
            $hours = round( $diff / 3600 );
            if ( $hours <= 1 ) {
                $hours = 1;
            }
            $since = sprintf( '%s hours', $hours );
        } elseif ( $diff < 604800 && $diff >= 86400 ) {
            $days = round( $diff / 86400 );
            if ( $days <= 1 ) {
                $days = 1;
            }
            $since = sprintf( '%s days', $days );
        } elseif ( $diff < 2592000 && $diff >= 604800 ) {
            $weeks = round( $diff / 604800 );
            if ( $weeks <= 1 ) {
                $weeks = 1;
            }
            $since = sprintf( '%s weeks', $weeks );
        } elseif ( $diff < 31536000 && $diff >= 2592000 ) {
            $months = round( $diff / 2592000 );
            if ( $months <= 1 ) {
                $months = 1;
            }
            $since = sprintf( '%s months', $months );
        } elseif ( $diff >= 31536000 ) {
            $years = round( $diff / 31536000 );
            if ( $years <= 1 ) {
                $years = 1;
            }
            $since = sprintf( '%s years', $years );
        }

        return $since;
    }

    public function local_date( $timestamp )
    {
        $date_format = 'd.m.Y';
        $timestamp = $this->adjust_timestamp( $timestamp );
        return date( $date_format, $timestamp );
    }

    public function local_time( $timestamp )
    {
        $time_format = 'H:i';
        $timestamp = $this->adjust_timestamp( $timestamp );
        return date( $time_format, $timestamp );
    }

    public function adjust_timestamp( $timestamp )
    {
        $gmt_offset = -3;
        $timestamp += 3600 * $gmt_offset;
        return $timestamp;
    }

    /**
     * Replaces double line breaks with paragraph elements.
     *
     * A group of regex replaces used to identify text formatted with newlines and
     * replace double line breaks with HTML paragraph tags. The remaining line breaks
     * after conversion become `<br />` tags, unless `$br` is set to '0' or 'false'.
     *
     * @since 0.71
     *
     * @param string $text The text which has to be formatted.
     * @param bool   $br   Optional. If set, this will convert all remaining line breaks
     *                     after paragraphing. Line breaks within `<script>`, `<style>`,
     *                     and `<svg>` tags are not affected. Default true.
     * @return string Text which has been converted into correct paragraph tags.
     */
    public function wpautop( $text, $br = true ) {
        $pre_tags = array();

        if ( trim( $text ) === '' ) {
            return '';
        }

        // Just to make things a little easier, pad the end.
        $text = $text . "\n";

        /*
         * Pre tags shouldn't be touched by autop.
         * Replace pre tags with placeholders and bring them back after autop.
         */
        if ( str_contains( $text, '<pre' ) ) {
            $text_parts = explode( '</pre>', $text );
            $last_part  = array_pop( $text_parts );
            $text       = '';
            $i          = 0;

            foreach ( $text_parts as $text_part ) {
                $start = strpos( $text_part, '<pre' );

                // Malformed HTML?
                if ( false === $start ) {
                    $text .= $text_part;
                    continue;
                }

                $name              = "<pre wp-pre-tag-$i></pre>";
                $pre_tags[ $name ] = substr( $text_part, $start ) . '</pre>';

                $text .= substr( $text_part, 0, $start ) . $name;
                ++$i;
            }

            $text .= $last_part;
        }
        // Change multiple <br>'s into two line breaks, which will turn into paragraphs.
        $text = preg_replace( '|<br\s*/?>\s*<br\s*/?>|', "\n\n", $text );

        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

        // Add a double line break above block-level opening tags.
        $text = preg_replace( '!(<' . $allblocks . '[\s/>])!', "\n\n$1", $text );

        // Add a double line break below block-level closing tags.
        $text = preg_replace( '!(</' . $allblocks . '>)!', "$1\n\n", $text );

        // Add a double line break after hr tags, which are self closing.
        $text = preg_replace( '!(<hr\s*?/?>)!', "$1\n\n", $text );

        // Standardize newline characters to "\n".
        $text = str_replace( array( "\r\n", "\r" ), "\n", $text );

        // Find newlines in all elements and add placeholders.
        $text = $this->wp_replace_in_html_tags( $text, array( "\n" => ' <!-- wpnl --> ' ) );

        // Collapse line breaks before and after <option> elements so they don't get autop'd.
        if ( str_contains( $text, '<option' ) ) {
            $text = preg_replace( '|\s*<option|', '<option', $text );
            $text = preg_replace( '|</option>\s*|', '</option>', $text );
        }

        /*
         * Collapse line breaks inside <object> elements, before <param> and <embed> elements
         * so they don't get autop'd.
         */
        if ( str_contains( $text, '</object>' ) ) {
            $text = preg_replace( '|(<object[^>]*>)\s*|', '$1', $text );
            $text = preg_replace( '|\s*</object>|', '</object>', $text );
            $text = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $text );
        }

        /*
         * Collapse line breaks inside <audio> and <video> elements,
         * before and after <source> and <track> elements.
         */
        if ( str_contains( $text, '<source' ) || str_contains( $text, '<track' ) ) {
            $text = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $text );
            $text = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $text );
            $text = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $text );
        }

        // Collapse line breaks before and after <figcaption> elements.
        if ( str_contains( $text, '<figcaption' ) ) {
            $text = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $text );
            $text = preg_replace( '|</figcaption>\s*|', '</figcaption>', $text );
        }

        // Remove more than two contiguous line breaks.
        $text = preg_replace( "/\n\n+/", "\n\n", $text );

        // Split up the contents into an array of strings, separated by double line breaks.
        $paragraphs = preg_split( '/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY );

        // Reset $text prior to rebuilding.
        $text = '';

        // Rebuild the content as a string, wrapping every bit with a <p>.
        foreach ( $paragraphs as $paragraph ) {
            $text .= '<p>' . trim( $paragraph, "\n" ) . "</p>\n";
        }

        // Under certain strange conditions it could create a P of entirely whitespace.
        $text = preg_replace( '|<p>\s*</p>|', '', $text );

        // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
        $text = preg_replace( '!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $text );

        // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
        $text = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $text );

        // In some cases <li> may get wrapped in <p>, fix them.
        $text = preg_replace( '|<p>(<li.+?)</p>|', '$1', $text );

        // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
        $text = preg_replace( '|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $text );
        $text = str_replace( '</blockquote></p>', '</p></blockquote>', $text );

        // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
        $text = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $text );

        // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
        $text = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $text );

        // Optionally insert line breaks.
        if ( $br ) {
            // Replace newlines that shouldn't be touched with a placeholder.
            $text = preg_replace_callback( '/<(script|style|svg|math).*?<\/\\1>/s', array($this, '_autop_newline_preservation_helper'), $text );

            // Normalize <br>.
            $text = str_replace( array( '<br>', '<br/>' ), '<br />', $text );

            // Replace any new line characters that aren't preceded by a <br /> with a <br />.
            $text = preg_replace( '|(?<!<br />)\s*\n|', "<br />\n", $text );

            // Replace newline placeholders with newlines.
            $text = str_replace( '<WPPreserveNewline />', "\n", $text );
        }

        // If a <br /> tag is after an opening or closing block tag, remove it.
        $text = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $text );

        // If a <br /> tag is before a subset of opening or closing block tags, remove it.
        $text = preg_replace( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $text );
        $text = preg_replace( "|\n</p>$|", '</p>', $text );

        // Replace placeholder <pre> tags with their original content.
        if ( ! empty( $pre_tags ) ) {
            $text = str_replace( array_keys( $pre_tags ), array_values( $pre_tags ), $text );
        }

        // Restore newlines in all elements.
        if ( str_contains( $text, '<!-- wpnl -->' ) ) {
            $text = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $text );
        }

        return $text;
    }

    /**
     * Replaces characters or phrases within HTML elements only.
     *
     * @since 4.2.3
     *
     * @param string $haystack      The text which has to be formatted.
     * @param array  $replace_pairs In the form array('from' => 'to', ...).
     * @return string The formatted text.
     */
    public function wp_replace_in_html_tags( $haystack, $replace_pairs ) {
        // Find all elements.
        $textarr = $this->wp_html_split( $haystack );
        $changed = false;

        // Optimize when searching for one item.
        if ( 1 === count( $replace_pairs ) ) {
            // Extract $needle and $replace.
            $needle  = array_key_first( $replace_pairs );
            $replace = $replace_pairs[ $needle ];

            // Loop through delimiters (elements) only.
            for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
                if ( str_contains( $textarr[ $i ], $needle ) ) {
                    $textarr[ $i ] = str_replace( $needle, $replace, $textarr[ $i ] );
                    $changed       = true;
                }
            }
        } else {
            // Extract all $needles.
            $needles = array_keys( $replace_pairs );

            // Loop through delimiters (elements) only.
            for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
                foreach ( $needles as $needle ) {
                    if ( str_contains( $textarr[ $i ], $needle ) ) {
                        $textarr[ $i ] = strtr( $textarr[ $i ], $replace_pairs );
                        $changed       = true;
                        // After one strtr() break out of the foreach loop and look at next element.
                        break;
                    }
                }
            }
        }

        if ( $changed ) {
            $haystack = implode( $textarr );
        }

        return $haystack;
    }

    /**
     * Separates HTML elements and comments from the text.
     *
     * @since 4.2.4
     *
     * @param string $input The text which has to be formatted.
     * @return string[] Array of the formatted text.
     */
    public function wp_html_split( $input ) {
        return preg_split( $this->get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
    }

    /**
     * Retrieves the regular expression for an HTML element.
     *
     * @since 4.4.0
     *
     * @return string The regular expression.
     */
    public function get_html_split_regex() {
        static $regex;

        if ( ! isset( $regex ) ) {
            // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
            $comments =
                '!'             // Start of comment, after the <.
                . '(?:'         // Unroll the loop: Consume everything until --> is found.
                .     '-(?!->)' // Dash not followed by end of comment.
                .     '[^\-]*+' // Consume non-dashes.
                . ')*+'         // Loop possessively.
                . '(?:-->)?';   // End of comment. If not found, match all input.

            $cdata =
                '!\[CDATA\['    // Start of comment, after the <.
                . '[^\]]*+'     // Consume non-].
                . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
                .     '](?!]>)' // One ] not followed by end of comment.
                .     '[^\]]*+' // Consume non-].
                . ')*+'         // Loop possessively.
                . '(?:]]>)?';   // End of comment. If not found, match all input.

            $escaped =
                '(?='             // Is the element escaped?
                .    '!--'
                . '|'
                .    '!\[CDATA\['
                . ')'
                . '(?(?=!-)'      // If yes, which type?
                .     $comments
                . '|'
                .     $cdata
                . ')';

            $regex =
                '/('                // Capture the entire match.
                .     '<'           // Find start of element.
                .     '(?'          // Conditional expression follows.
                .         $escaped  // Find end of escaped element.
                .     '|'           // ...else...
                .         '[^>]*>?' // Find end of normal element.
                .     ')'
                . ')/';
            // phpcs:enable
        }

        return $regex;
    }

    public function saveCryptoWallet(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:10',
            'address' => 'required|string|max:255',
            'details' => 'nullable|string|max:500',
        ]);

        // Get existing wallets
        $wallets = $this->getWallets();

        // Add new wallet
        $walletId = uniqid('wallet_');
        $wallets[] = [
            'id' => $walletId,
            'currency' => $validated['currency'],
            'address' => $validated['address'],
            'details' => $validated['details'] ?? null,
            'created_at' => now()->toDateTimeString(),
        ];

        // Save back to settings
        Setting::updateOrCreate(
            ['key' => 'mcc_wallets'],
            ['value' => json_encode($wallets), 'autoload' => true]
        );

        return response()->json([
            'success' => true,
            'wallet' => [
                'id' => $walletId,
                'currency' => $validated['currency'],
                'address' => $validated['address'],
                'details' => $validated['details'] ?? null,
            ]
        ]);
    }

    public function deleteCryptoWallet($id)
    {
        $wallets = $this->getWallets();
        $initialCount = count($wallets);

        // Remove wallet with the given ID
        $wallets = array_filter($wallets, function($wallet) use ($id) {
            return ($wallet['id'] ?? '') !== $id;
        });

        if (count($wallets) < $initialCount) {
            // Save updated wallets if something was removed
            Setting::updateOrCreate(
                ['key' => 'mcc_wallets'],
                ['value' => json_encode(array_values($wallets)), 'autoload' => true]
            );

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Wallet not found']);
    }

    private function getWallets()
    {
        $setting = Setting::where('key', 'mcc_wallets')->first();
        return $setting ? (json_decode($setting->value, true) ?: []) : [];
    }
}
