<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Log;

class Newpost extends Model
{

    /**
     * Запрос в API Новой почты
     *
     * @param $parameters
     * @return bool|mixed|void
     */
    public function requestToAPI($parameters){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.novaposhta.ua/v2.0/json/');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if($response['success'] == true) {
            return $response;
        }

        return false;
    }

    /**
     * Обновление всех данных из API
     *
     * @return bool
     */
    public function updateAll(){
        if($this->getRegions(true) && $this->getAllCities(time()) && $this->getAllWarehouses(time())){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получение списка областей из БД или из API
     *
     * @param bool $force
     * @return mixed
     */
    public function getRegions($force = false){
        $setting = new Setting;
        $newpost = $setting->get_setting('newpost_regions_last_update');
        $update_period = $setting->get_setting('newpost_regions_update_period');
        $time = time();

        if(empty($newpost->newpost_regions_last_update) || ($newpost->newpost_regions_last_update + $update_period) < $time || $force){
            $parameters = [
                'modelName' => 'Address',
                'calledMethod' => 'getAreas',
                'apiKey' => $setting->get_setting('newpost_api_key')
            ];

            $result = $this->requestToAPI($parameters);

            if(!empty($result['success'])) {
                foreach ($result['data'] as $region){
                    $r = DB::table('newpost_regions')->where('region_id', $region['Ref'])->first();
                    if(empty($r)){
                        DB::table('newpost_regions')->insert([
                            'region_id' => $region['Ref'],
                            'name_ua' => $region['Description'],
                            'name_ru' => $region['Description'],
                            'region_center' => $region['AreasCenter']
                        ]);
                    }else{
                        DB::table('newpost_regions')->where('region_id', $region['Ref'])->update([
                            'name_ua' => $region['Description'],
                            'region_center' => $region['AreasCenter']
                        ]);
                    }
                }

                $setting->update_setting('newpost_regions_last_update', $time);
            } elseif (!empty($result['error'])) {
                Log::error('Ошибка API Новой Почты:', $result['error']);
            }
        }

        return DB::table('newpost_regions')->get();
    }

    /**
     * Получение информации об области из БД
     *
     * @param $region_id
     * @return bool
     */
    public function getRegionRef($region_id){
        $result = DB::table('newpost_regions')->find($region_id);

        if ($result){
            return $result;
        }
        return false;
    }

    /**
     * Получение списка всех городов из API
     *
     * @param $time
     * @return bool
     */
    public function getAllCities($time){
        $setting = new Setting;
        $parameters = [
            'modelName' => 'Address',
            'calledMethod' => 'getCities',
            'apiKey' => $setting->get_setting('newpost_api_key')
        ];

        $result = $this->requestToAPI($parameters);

        $cities = [];
        if(!empty($result['success'])) {
            $saved = DB::table('newpost_cities')->select('city_id')->get()->pluck('city_id')->toArray();
            foreach ($result['data'] as $city) {
                if(!in_array($city['Ref'], $saved)){
                    $cities[] = [
                        'city_id'   => $city['Ref'],
                        'name_ua'   => $city['Description'],
                        'name_ru'   => $city['DescriptionRu'],
                        'region_id' => $city['Area']
                    ];
                }
            }
//            DB::table('newpost_cities')->truncate();
            DB::table('newpost_cities')->insert($cities);

            $setting->update_setting('newpost_cities_last_update', $time);
            return true;
        } elseif (!empty($result['error'])) {
            Log::error('Ошибка API Новой Почты:', $result['error']);
        }
        return false;
    }

    /**
     * Получение списка городов области по id области
     *
     * @param $region_id
     * @return array
     */
    public function getCities($region_id){
        $setting = new Setting;
        $last_update =  $setting->get_setting('newpost_cities_last_update');
        if(empty($last_update)){
            $last_update = config('newpost.cities_last_update');
        }
        $time = time();

        if(is_null($last_update) || ($last_update + 2592000) < $time){
            $this->getAllCities($time);
        }

        $result = DB::table('newpost_cities')->where('region_id', $region_id)->get();

        if ($result) {
            return $result;
        }
        return [];
    }

    /**
     * Получение информации о городе по его id
     *
     * @param $city_id
     * @return bool
     */
    public function getCityRef($city_id){
        $result = DB::table('newpost_cities')->find($city_id);

        if ($result){
            return $result;
        }
        return false;
    }

    public function getCityByCid($cid){
        $result = DB::table('newpost_cities')->where('city_id', $cid)->first();
        if(empty($result))
            $result = DB::table('newpost_cities')->where('id', $cid)->first();

        if ($result){
            return $result;
        }
        return false;
    }

    /**
     * Получение списка всех отделений Новой Почты из API
     *
     * @param $time
     * @return bool
     */
    public function getAllWarehouses($time){
        $setting = new Setting;
        $parameters = [
            'modelName' => 'Address',
            'calledMethod' => 'getWarehouses',
            'apiKey' => $setting->get_setting('newpost_api_key')
        ];

        $result = $this->requestToAPI($parameters);

        $warehouses = [];
        if(!empty($result['success'])) {
            $saved = DB::table('newpost_warehouses')->select('warehouse_id')->get()->pluck('warehouse_id')->toArray();
            foreach ($result['data'] as $warehouse) {
                if(!in_array($warehouse['Ref'], $saved)){
                    $warehouses[] = [
                        'warehouse_id'  => $warehouse['Ref'],
                        'address_ua'    => $warehouse['Description'],
                        'address_ru'    => $warehouse['DescriptionRu'],
                        'number'        => $warehouse['Number'],
                        'city_id'       => $warehouse['CityRef'],
                        'phone'         => $warehouse['Phone']
                    ];
                }
            }
//            DB::table('newpost_warehouses')->truncate();
            DB::table('newpost_warehouses')->insert($warehouses);

            $setting->update_setting('newpost_warehouses_last_update', $time);
            return true;
        } elseif (!empty($result['error'])) {
            Log::error('Ошибка API Новой Почты:', $result['error']);
        }
        return false;
    }

    /**
     * Получение списка всех отделений города по его id
     *
     * @param $city_id
     * @return array
     */
    public function getWarehouses($city_id){
        $setting = new Setting;
        $last_update =  $setting->get_setting('newpost_warehouses_last_update');
        if(empty($last_update)){
            $last_update = config('newpost.warehouses_last_update');
        }
        $time = time();

        if(is_null($last_update) || ($last_update + 604800) < $time){
            $this->getAllWarehouses($time);
        }

        $result = DB::table('newpost_warehouses')->where('city_id', $city_id)->get();

        if ($result) {
            return $result;
        }
        return [];
    }

    /**
     * Получение информации об отделении по его id
     *
     * @param $warehouse_id
     * @return bool
     */
    public function getWarehouse($warehouse_id){
        $result = DB::table('newpost_warehouses')->find($warehouse_id);

        if ($result){
            return $result;
        }
        return false;
    }

    public function getWarehouseByWid($wid){
        $result = DB::table('newpost_warehouses')->where('warehouse_id', $wid)->first();
        if(empty($result))
            $result = DB::table('newpost_warehouses')->where('id', $wid)->first();

        if ($result){
            return $result;
        }
        return false;
    }

    /**
     * Поиск города по названию
     *
     * @param $name
     * @return Model|null|object|static
     */
    public function findCity($name){
        return DB::table('newpost_cities')->where('name_ru', $name)->orWhere('name_ua', $name)->first();
    }

    //https://devcenter.novaposhta.ua/docs/services/556eef34a0fe4f02049c664e/operations/56261f14a0fe4f1e503fe187
    public function createTtn($order, $weight, $volume, $description, $date){
        $setting = new Setting;
        $delivery_info = json_decode($order->delivery, true);
        $parameters = [
            'apiKey' => $setting->get_setting('newpost_api_key'),
            'modelName' => 'InternetDocument',
            'calledMethod' => 'save',
            'methodProperties' => [
                'NewAddress' => '1',
                'PayerType' => 'Recipient',
                'PaymentMethod' => 'Cash',
                'CargoType' => 'Cargo',
                'VolumeGeneral' => $volume,
                'Weight' => $weight,
                'ServiceType' => 'WarehouseWarehouse',
                'SeatsAmount' => '1',
                'Description' => $description,
                'Cost' => $order->total_price,
                'CitySender' => $setting->get_setting('newpost_city_key'),
                'Sender' => $setting->get_setting('newpost_sender_key'),
                'SenderAddress' => $setting->get_setting('newpost_sender_address_key'),
                'ContactSender' => $setting->get_setting('newpost_sender_contact'),
                'SendersPhone' => $setting->get_setting('newpost_sender_phone'),
                'RecipientCityName' => $delivery_info['info']['city'],
                'RecipientArea' => $delivery_info['info']['region'],
                'RecipientAreaRegions' => '',
                'RecipientAddressName' => $delivery_info['info']['warehouse'],
                'RecipientHouse' => '',
                'RecipientFlat' => '',
                'RecipientName' => isset($order->user->name) ? $order->user->name : '',
                'RecipientType' => 'PrivatePerson',
                'RecipientsPhone' => isset($order->user->phone) ? $order->user->phone : '',
                'DateTime' => $date
            ]
        ];

        $result = $this->requestToAPI($parameters);

        dd($result);
    }

    /**
     * Поиск контрагентов
     *
     * @param $name
     * @param string $property
     * @return bool|mixed|void
     */
    public function getCounterparties($name, $property = 'Recipient'){
        $setting = new Setting;

        $parameters = [
            'apiKey' => $setting->get_setting('newpost_api_key'),
            'modelName' => 'Counterparty',
            'calledMethod' => 'getCounterparties',
            'methodProperties' => [
                'CounterpartyProperty' => $property,
                'Page' => 1
            ]
        ];

        if(!empty($name)){
            $parameters['methodProperties']['FindByString'] = $name;
        }

        return $this->requestToAPI($parameters);
    }

    public function getContacts($counterparty_id){
        $setting = new Setting;

        $parameters = [
            'apiKey' => $setting->get_setting('newpost_api_key'),
            'modelName' => 'Counterparty',
            'calledMethod' => 'getCounterpartyContactPersons',
            'methodProperties' => [
                'Ref' => $counterparty_id,
                'Page' => 1
            ]
        ];

        $result = $this->requestToAPI($parameters);

        return $result;
    }
}
