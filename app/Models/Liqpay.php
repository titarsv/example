<?php

namespace App\Models;

use League\Flysystem\Exception;

/**
 * Liqpay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        LiqPay
 * @package         liqpay/liqpay
 * @version         3.0
 * @author          Liqpay
 * @copyright       Copyright (c) 2014 Liqpay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * LIQPAY API       https://www.liqpay.com/ru/doc
 *
 */
/**
 * Payment method liqpay process
 *
 * @author      Liqpay <support@liqpay.com>
 */
class Liqpay
{
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_RUR = 'RUR';
    private $_api_url = 'https://www.liqpay.ua/api/';
    private $_checkout_url = 'https://www.liqpay.ua/api/3/checkout';
    protected $_supportedCurrencies = array(
        self::CURRENCY_EUR,
        self::CURRENCY_USD,
        self::CURRENCY_UAH,
        self::CURRENCY_RUB,
        self::CURRENCY_RUR,
    );
    private $_public_key;
    private $_private_key;

    /**
     * Constructor.
     *
     * @param string $public_key
     * @param string $private_key
     *
     * @throws Exception
     */
    public function __construct($public_key, $private_key)
    {
        if (empty($public_key)) {
            throw new Exception('public_key is empty');
        }
        if (empty($private_key)) {
            throw new Exception('private_key is empty');
        }
        $this->_public_key = $public_key;
        $this->_private_key = $private_key;
    }

    /**
     * Call API
     *
     * @param $path
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function api($path, $params = array())
    {
        if(!isset($params['version'])){
            throw new Exception('version is null');
        }
        $url         = $this->_api_url . $path;
        $public_key  = $this->_public_key;
        $private_key = $this->_private_key;
        $data        = base64_encode(json_encode(array_merge(compact('public_key'), $params)));
        $signature   = base64_encode(sha1($private_key.$data.$private_key, 1));
        $postfields  = http_build_query(array(
            'data'  => $data,
            'signature' => $signature
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $server_output = curl_exec($ch);
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * cnb_form
     *
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function cnb_form($params)
    {
        $language = 'ru';
        if (isset($params['language']) && $params['language'] == 'en') {
            $language = 'en';
        }
        $params    = $this->cnb_params($params);
        $data      = base64_encode( json_encode($params) );
        $signature = $this->cnb_signature($params);

        return [
            'url'   => $this->_checkout_url,
            'data'  => $data,
            'signature' => $signature
        ];
    }

    /**
     * cnb_signature
     *
     * @param array $params
     * @return string
     */
    public function cnb_signature($params)
    {
        $params      = $this->cnb_params($params);
        $private_key = $this->_private_key;
        $json      = base64_encode( json_encode($params) );
        $signature = $this->str_to_sign($private_key . $json . $private_key);
        return $signature;
    }

    /**
     * cnb_params
     *
     * @param $params
     * @return mixed
     * @throws Exception
     */
    private function cnb_params($params)
    {

        $params['public_key'] = $this->_public_key;
        if (!isset($params['version'])) {
            throw new Exception('version is null');
        }
        if (!isset($params['amount'])) {
            throw new Exception('amount is null');
        }
        if (!isset($params['currency'])) {
            throw new Exception('currency is null');
        }
        if (!in_array($params['currency'], $this->_supportedCurrencies)) {
            throw new Exception('currency is not supported');
        }
        if ($params['currency'] == self::CURRENCY_RUR) {
            $params['currency'] = self::CURRENCY_RUB;
        }
        if (!isset($params['description'])) {
            throw new Exception('description is null');
        }
        return $params;
    }
    /**
     * str_to_sign
     *
     * @param string $str
     *
     * @return string
     */
    public function str_to_sign($str)
    {
        $signature = base64_encode(sha1($str,1));
        return $signature;
    }

    public function returnOrderStatus($status)
    {
        $liqpay_status = [
            'success'           => 3,
            'failure'           => 7,
            'error'             => 8,
            'reversed'          => 9,
            'sandbox'           => 10,
            'otp_verify'        => 11,
            '3ds_verify'        => 11,
            'cvv_verify'        => 11,
            'sender_verify'     => 11,
            'processing'        => 12,
            'prepared'          => 13,
            'wait_accept'       => 3,
            'wait_secure'       => 14,
            'wait_compensation' => 3
        ];

        if (isset($liqpay_status[$status])) {
            return $liqpay_status[$status];
        } else {
            return false;
        }
    }
}
