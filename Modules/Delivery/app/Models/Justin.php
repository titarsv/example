<?php

namespace Modules\Delivery\Models;

use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App;

// https://justin.ua/api/api_justin_documentation.pdf
class Justin
{
    private $production = true;
    private $apiKey = null;
    private $accountKey = null;

    public function __construct(){
        $setting = new Setting;
        $this->accountKey = $setting->get_setting('justin_account_key');
        $this->apiKey = $setting->get_setting('justin_api_key');
        if(empty($this->apiKey)){
            $this->apiKey = 'f2290c07-c028-11e9-80d2-525400fb7782';
            $this->production = false;
        }
    }

    public function getRegions(){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return [];
        }

        $regions = Cache::remember('justin_regions_'.$locale, 10080, function() use($locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'catalog',
                'name' => 'cat_Region',
                'params' => [
                    'language' => $locale
                ]
            ];

            $response = $this->request($url, 'POST', $data);
            $regions = [];
            if(!empty($response->data)){
                foreach($response->data as $region){
                    $regions[$region->fields->uuid] = [
                        'name' => $region->fields->descr
                    ];
                }
            }

            uasort($regions, array($this, 'sort'));

            return $regions;
        });

        return $regions;
    }

    public function getCities($region_uuid){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return [];
        }

        $cities = Cache::remember('justin_cities_'.$region_uuid.'_'.$locale, 10080, function() use($region_uuid, $locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'catalog',
                'name' => 'cat_Cities',
                'params' => [
                    'language' => $locale
                ],
                'filter' => [[
                    'name' => 'objectOwner',
                    'comparison' => 'equal',
                    'leftValue' => $region_uuid
                ]]
            ];

            $response = $this->request($url, 'POST', $data);
            $cities = [];
            foreach($response->data as $city){
                $cities[$city->fields->uuid] = [
                    'name' => $city->fields->descr
                ];
            }

            uasort($cities, array($this, 'sort'));

            return $cities;
        });

        return $cities;
    }

    public function getRegionNameByUuid($uuid){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return null;
        }

        $region_name = Cache::remember('justin_region_'.$uuid.'_'.$locale, 10080, function() use($uuid, $locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'catalog',
                'name' => 'cat_Region',
                'TOP' => 1,
                'params' => [
                    'language' => $locale
                ],
                'filter' => [[
                    'name' => 'uuid',
                    'comparison' => 'equal',
                    'leftValue' => $uuid
                ]]
            ];

            $response = $this->request($url, 'POST', $data);

            if(!empty($response->data)){
                foreach($response->data as $region){
                    if($region->fields->uuid == $uuid)
                        return $region->fields->descr;
                }
            }

            return null;
        });

        return $region_name;
    }

    public function getCityNameByUuid($uuid){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return null;
        }

        $city_name = Cache::remember('justin_city_'.$uuid.'_'.$locale, 10080, function() use($uuid, $locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'catalog',
                'name' => 'cat_Cities',
                'params' => [
                    'language' => $locale
                ],
                'filter' => [[
                    'name' => 'uuid',
                    'comparison' => 'equal',
                    'leftValue' => $uuid
                ]]
            ];

            $response = $this->request($url, 'POST', $data);
            if(!empty($response->data)){
                foreach($response->data as $city){
                    if($city->fields->uuid == $uuid)
                        return $city->fields->descr;
                }
            }

            return null;
        });

        return $city_name;
    }

    public function getAllWarehouses(){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return [];
        }

        $warehouses = Cache::remember('justin_warehouses_'.$locale, 10080, function() use($locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'request',
                'name' => 'req_DepartmentsLang',
                'language' => $locale,
                'params' => [
                    'language' => $locale
                ],
                'filter' => []
            ];

            $response = $this->request($url, 'POST', $data);

            $warehouses = [];
            foreach($response->data as $warehouse){
                $warehouses[$warehouse->fields->Depart->uuid] = [
                    'name' => $warehouse->fields->descr . ' ('.$warehouse->fields->street->descr.(!empty($warehouse->fields->houseNumber) ? ' '.$warehouse->fields->houseNumber : '').')'
                ];
            }

            uasort($warehouses, array($this, 'sort'));

            return $warehouses;
        });

        return $warehouses;
    }

    public function getWarehouses($city){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return [];
        }

        $warehouses = Cache::remember('justin_warehouses_'.md5($city).'_'.$locale, 10080, function() use($city, $locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'request',
                'name' => 'req_DepartmentsLang',
                'language' => $locale,
                'params' => [
                    'language' => $locale
                ],
                'filter' => [[
                    'name' => 'city',
                    'comparison' => 'equal',
                    'leftValue' => $city
                ]]
            ];

            $response = $this->request($url, 'POST', $data);

            $warehouses = [];
            foreach($response->data as $warehouse){
                $warehouses[$warehouse->fields->Depart->uuid] = [
                    'name' => $warehouse->fields->descr . ' ('.$warehouse->fields->street->descr.(!empty($warehouse->fields->houseNumber) ? ' '.$warehouse->fields->houseNumber : '').')',
                    'branch' => $warehouse->fields->branch
                ];
            }

            uasort($warehouses, array($this, 'sort'));

            return $warehouses;
        });

        return $warehouses;
    }

    public function getWarehouseById($id){
        $locale = App::getLocale();

        if(empty($this->accountKey)){
            return null;
        }

        $warehouse_name = Cache::remember('justin_warehouse_'.$id.'_'.$locale, 10080, function() use($id, $locale){
            $url = 'https://api.justin.ua/justin_pms/hs/v2/runRequest';

            $data = [
                'keyAccount' => $this->accountKey,
                'sign' => sha1('$DHCvusW:'.date('Y-m-d')),
                'request' => 'getData',
                'type' => 'request',
                'name' => 'req_DepartmentsLang',
                'language' => $locale,
                'params' => [
                    'language' => $locale
                ],
                'filter' => [[
                    'name' => 'objectOwner',
                    'comparison' => 'equal',
                    'leftValue' => $id
                ]]
            ];

            $response = $this->request($url, 'POST', $data);
            if(!empty($response->data)){
                foreach($response->data as $warehouse){
                    if($warehouse->fields->Depart->uuid == $id)
                        return $warehouse->fields->descr . ' ('.$warehouse->fields->street->descr.(!empty($warehouse->fields->houseNumber) ? ' '.$warehouse->fields->houseNumber : '').')';
                }
            }

            return null;
        });

        return $warehouse_name;
    }

    private function sort($x, $y){
        if($x['name'] == $y['name'])
            return 0;
        elseif($x['name'] < $y['name'])
            return -1;
        else
            return 1;
    }

    public function request($url, $method = 'GET', $data = []){
        $ch = curl_init();
        switch ($method){
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                if($data)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                if($data)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                break;
            default:
                if($data){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                }
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSLVERSION, 6);
        $output = curl_exec($ch);
        curl_close($ch);
        if(substr($output, 0, 1) !== '{' && substr($output, 0, 1) !== '['){
            return $output;
        }
        return json_decode($output);
    }

    public function createTtn($sender, $recipient, $params){
        $url = $this->production ? 'https://api.justin.ua/justin_pms/hs/api/v1/documents/orders' : 'https://api.sandbox.justin.ua/client_api/hs/api/v1/documents/orders';

        $data = [
            'api_key' => $this->apiKey,
            'data' => array_merge($sender, $recipient, $params)
        ];

        return $this->request($url, 'POST', $data);
    }
}