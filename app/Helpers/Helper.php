<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use App;

class Helper
{
    public static function applClasses()
    {
        // default data value
        $dataDefault = [
            'mainLayoutType' => 'vertical-menu',
            'theme' => 'light',
            'isContentSidebar'=> false,
            'pageHeader' => false,
            'bodyCustomClass' => '',
            'navbarBgColor' => 'bg-white',
            'navbarType' => 'fixed',
            'isMenuCollapsed' => false,
            'footerType' => 'static',
            'templateTitle' => '',
            'isCustomizer' => true,
            'isCardShadow' => true,
            'isScrollTop' => true,
            'defaultLanguage' => 'en',
            'direction' => env('MIX_CONTENT_DIRECTION', 'ltr'),
        ];

        //if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
        $data = array_merge($dataDefault, config('custom.custom'));

        // all available option of materialize template
        $allOptions = [
            'mainLayoutType' => array('vertical-menu','horizontal-menu','vertical-menu-boxicons'),
            'theme' => array('light'=>'light','dark'=>'dark','semi-dark'=>'semi-dark'),
            'isContentSidebar'=> array(false,true),
            'pageHeader' => array(false,true),
            'bodyCustomClass' => '',
            'navbarBgColor' => array('bg-white','bg-primary', 'bg-success','bg-danger','bg-info','bg-warning','bg-dark'),
            'navbarType' => array('fixed'=>'fixed','static'=>'static','hidden'=>'hidden'),
            'isMenuCollapsed' => array(false,true),
            'footerType' => array('fixed'=>'fixed','static'=>'static','hidden'=>'hidden'),
            'templateTitle' => '',
            'isCustomizer' => array(true,false),
            'isCardShadow' => array(true,false),
            'isScrollTop' => array(true,false),
            'defaultLanguage'=>array('en' => 'en','pt' => 'pt','fr' => 'fr','de' => 'de'),
            'direction' => array('ltr' => 'ltr','rtl' => 'rtl'),
        ];
        // navbar body class array
        $navbarBodyClass = [
            'fixed'=>'navbar-sticky',
            'static'=>'navbar-static',
            'hidden'=>'navbar-hidden',
        ];
        $navbarClass  = [
            'fixed'=>'fixed-top',
            'static'=>'navbar-static-top',
            'hidden'=>'d-none',
        ];
        // footer class
        $footerBodyClass = [
            'fixed'=>'fixed-footer',
            'static'=>'footer-static',
            'hidden'=>'footer-hidden',
        ];
        $footerClass = [
            'fixed'=>'footer-sticky',
            'static'=>'footer-static',
            'hidden'=>'d-none',
        ];

        //if any options value empty or wrong in custom.php config file then set a default value
        foreach ($allOptions as $key => $value) {
            if (gettype($data[$key]) === gettype($dataDefault[$key])) {
                if (is_string($data[$key])) {
                    if(is_array($value)){

                        $result = array_search($data[$key], $value);
                        if (empty($result)) {
                            $data[$key] = $dataDefault[$key];
                        }
                    }
                }
            } else {
                if (is_string($dataDefault[$key])) {
                    $data[$key] = $dataDefault[$key];
                } elseif (is_bool($dataDefault[$key])) {
                    $data[$key] = $dataDefault[$key];
                } elseif (is_null($dataDefault[$key])) {
                    is_string($data[$key]) ? $data[$key] = $dataDefault[$key] : '';
                }
            }
        }

        //  above arrary override through dynamic data
        $layoutClasses = [
            'mainLayoutType' => $data['mainLayoutType'],
            'theme' => $data['theme'],
            'isContentSidebar'=> $data['isContentSidebar'],
            'pageHeader' => $data['pageHeader'],
            'bodyCustomClass' => $data['bodyCustomClass'],
            'navbarBgColor' => $data['navbarBgColor'],
            'navbarType' => $navbarBodyClass[$data['navbarType']],
            'navbarClass' => $navbarClass[$data['navbarType']],
            'isMenuCollapsed' => $data['isMenuCollapsed'],
            'footerType' => $footerBodyClass[$data['footerType']],
            'footerClass' => $footerClass[$data['footerType']],
            'templateTitle' => $data['templateTitle'],
            'isCustomizer' => $data['isCustomizer'],
            'isCardShadow' => $data['isCardShadow'],
            'isScrollTop' => $data['isScrollTop'],
            'defaultLanguage' => $data['defaultLanguage'],
            'direction' => $data['direction'],
        ];

        // set default language if session hasn't locale value the set default language
//        if(!session()->has('locale')){
//            app()->setLocale($layoutClasses['defaultLanguage']);
//        }

        return $layoutClasses;
    }
    // updatesPageConfig function override all configuration of custom.php file as page requirements.
    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        $custom = 'custom';
        if (isset($pageConfigs)) {
            if (count($pageConfigs) > 0) {
                foreach ($pageConfigs as $config => $val) {
                    Config::set($demo . '.' . $custom . '.' . $config, $val);
                }
            }
        }
    }

    public static function baseUrl($path = '/'){
        if($path == '/'.ltrim(request()->path(), '/'))
            return 'javascript:void(0)';

        return env('APP_URL').(app()->getLocale() != config()->get('app.locale') ? '/'.app()->getLocale().$path : $path);
    }

    static function localizationFields($fields){
        $locales = Config::get('app.locales');
        $localization_fields = [];
        foreach($fields as $field){
            if(count($locales) > 1){
                foreach($locales as $locale){
                    $localization_fields[] = $field.'_'.$locale;
                }
            }else{
                $localization_fields[] = $field;
            }
        }

        return $localization_fields;
    }

    static function translit($string, $reverse = false){
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => "",    'ы' => 'y',   'ъ' => "",
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => "",    'Ы' => 'Y',   'Ъ' => "",
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );
        if($reverse){
            unset($converter['ь']);
            unset($converter['ъ']);
            unset($converter['Ь']);
            unset($converter['Ъ']);
            $converter = array_flip($converter);
        }
        return strtr($string, $converter);
    }

    public static function isLighthouse(){
        return !(!isset($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false);
    }

    public static function isBot(){
        if(!empty($_SERVER['HTTP_USER_AGENT'])){
            $options = array(
                'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot','YandexDirectDyn',
                'YandexScreenshotBot', 'YandexImages', 'YandexVideo', 'YandexVideoParser',
                'YandexMedia', 'YandexBlogs', 'YandexFavicons', 'YandexWebmaster',
                'YandexPagechecker', 'YandexImageResizer','YandexAdNet', 'YandexDirect',
                'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika',
                'YandexNews', 'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus',
                'YandexMarket', 'YandexVertis', 'YandexForDomain', 'YandexSpravBot',
                'YandexSearchShop', 'YandexMedianaBot', 'YandexOntoDB', 'YandexOntoDBAPI',
                'Googlebot', 'Googlebot-Image', 'Mediapartners-Google', 'AdsBot-Google',
                'Mail.RU_Bot', 'bingbot', 'Accoona', 'ia_archiver', 'Ask Jeeves',
                'OmniExplorer_Bot', 'W3C_Validator', 'WebAlta', 'YahooFeedSeeker', 'Yahoo!',
                'Ezooms', '', 'Tourlentabot', 'MJ12bot', 'AhrefsBot', 'SearchBot', 'SiteStatus',
                'Nigma.ru', 'Baiduspider', 'Statsbot', 'SISTRIX', 'AcoonBot', 'findlinks',
                'proximic', 'OpenindexSpider','statdom.ru', 'Exabot', 'Spider', 'SeznamBot',
                'oBot', 'C-T bot', 'Updownerbot', 'Snoopy', 'heritrix', 'Yeti',
                'DomainVader', 'DCPbot', 'PaperLiBot'
            );

            foreach($options as $row) {
                if(stripos($_SERVER['HTTP_USER_AGENT'], $row) !== false){
                    return true;
                }
            }
        }

        return false;
    }

    public static function formattedPrice($price){
        $currencies = config()->get('app.currencies');
        $currency = config()->get('app.currency');

        $country = current_country(request());

        if(!empty($country)){
            $price_coefficient = (100 + $country->price_coefficient) / 100;
        }else{
            $price_coefficient = 1;
        }

        if($currency == 'eur' || !isset($currencies[$currency])){
            return $currencies[$currency].' '.number_format($price * $price_coefficient, 0, ',', ' ');
        }elseif($currency == 'uah'){
            $rate = config()->get('app.rate');
            return number_format($price * $price_coefficient / $rate, 0, ',', ' ').' '.$currencies[$currency];
        }else{
            $rate = config()->get('app.rate');
            return $currencies[$currency].' '.number_format($price * $price_coefficient / $rate, 0, ',', ' ');
        }
    }

    public static function acceptedTypes(){
        $accepted_types = config()->get('app.accepted_image_types');
        if(empty($accepted_type)){
//            $accepted_types = ['jpg', 'jpeg', 'bmp'];
            $accepted_types = ['jpg', 'jpeg', 'bmp', 'webp', 'avif'];
            $accept = request()->header('accept');
            if(strpos($accept, 'image/png') !== false){
                $accepted_types[] = 'png';
            }
            if((strpos($accept, 'image/webp') !== false || (isset($_COOKIE['supportWebp']) && $_COOKIE['supportWebp'] === 'true'))){
                $accepted_types[] = 'webp';
            }
            if((strpos($accept, 'image/avif') !== false || (isset($_COOKIE['supportAvif']) && $_COOKIE['supportAvif'] === 'true')) && function_exists('imageavif')){
                $accepted_types[] = 'avif';
            }

            config()->set('app.accepted_image_types', $accepted_types);
        }

        return $accepted_types;
    }

    public static function acceptedType($default){
        $accepted_types = acceptedTypes();
        if(in_array('avif', $accepted_types)){
            return 'avif';
        }elseif(in_array('webp', $accepted_types)){
            return 'webp';
        }

        return $default;
    }

    public static function getChildrenEditors($fields, $parent, $data, $lang){
        $editors = [];
        foreach($fields as $field){
            if(!empty($data)){
                foreach($data as $i => $d){
                    if($field->type == 'wysiwyg'){
                        if(!empty($field->langs)){
                            $editors[] = 'fields'.$lang.$parent.$i.$field->slug;
                        }else{
                            $editors[] = 'fields'.'all'.$parent.$i.$field->slug;
                        }
                    }elseif($field->type == 'repeater'){
                        if(isset($d->{$field->slug})){
                            $editors = array_merge($editors, self::getChildrenEditors($field->fields, $parent.$i.$field->slug, $d->{$field->slug}, $lang));
                        }
                    }
                }
            }else{
                if($field->type == 'wysiwyg'){
                    if(!empty($field->langs)){
                        $editors[] = 'fields'.$lang.$parent.'0'.$field->slug;
                    }else{
                        $editors[] = 'fields'.'all'.$parent.'0'.$field->slug;
                    }
                }elseif($field->type == 'repeater'){
                    $editors = array_merge($editors, self::getChildrenEditors($field->fields, $parent.'0'.$field->slug, [], $lang));
                }
            }
        }

        return $editors;
    }

    public static function getDefaultCurrencySymbol(){
        return config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.symbol');
    }
}
