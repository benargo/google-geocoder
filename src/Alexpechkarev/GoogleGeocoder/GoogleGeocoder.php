<?php

/**
 * Description of GoogleGeocoder
 *
 * @author Alexander Pechkarev <alexpechkarev@gmail.com>
 */

namespace Alexpechkarev\GoogleGeocoder;

use Cache;
use Carbon\Carbon;

class GoogleGeocoder {

    /*
    |--------------------------------------------------------------------------
    | Application Key
    |--------------------------------------------------------------------------
    |
    | Your application's API key. This key identifies your application for
    | purposes of quota management. Learn how to get a key from the APIs Console.
    */
    protected $applicationKey;


    /*
    |--------------------------------------------------------------------------
    | Request Url
    |--------------------------------------------------------------------------
    |
    */
    protected $requestUrl;

    /*
    |--------------------------------------------------------------------------
    | Requested Format
    |--------------------------------------------------------------------------
    |
    */
    protected $format;

    /*
    |--------------------------------------------------------------------------
    | Geocoding request parameters
    |--------------------------------------------------------------------------
    |
    */
    protected $param;

    /*
    |--------------------------------------------------------------------------
    | Caching parameters
    |--------------------------------------------------------------------------
    |
    */
    protected $caching;



    /**
     * Set Application Key and Request URL
     *
     * @param string $format - output format json or xml
     * @param array $param - geocoding request parameters
     */
    public function __construct($config)
    {
        $this->applicationKey   = $config['applicationKey'];
        $this->requestUrl       = $config['requestUrl'];
        $this->caching          = $config['caching'];
    }


    /**
     * Make cURL call
     * @return string
     * @throws \RuntimeException
     */
    protected function call()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER      => 1,
            CURLOPT_URL                 => $this->requestUrl[$this->format].$this->param,
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_FAILONERROR         => true,
        ));

        $request = curl_exec($curl);

        if (empty($request)) {
            throw new \RuntimeException('cURL request retuened following error: '.curl_error($curl) );
        }

        curl_close($curl);

        if ($this->caching['enabled'] === true) {
          if (is_int($this->caching['duration'])) {
            $expires = Carbon::now()->addMinutes($this->caching['duration']);

            Cache::put($this->caching['key'], serialize($request), $expires);
          }
          elseif ($this->caching['duration'] == 'forever') {
            Cache::forever($this->caching['key'], serialize($request));
          }
        }

        return $request;
    }


    /**
     * Geocoding request
     *
     * @param string $format - output format json or xml
     * @param array $param - geocoding request parameters
     *
     * @return string
     */
    public function geocode($format, $param)
    {
        $this->format     = array_key_exists($format, $this->requestUrl) ? $format : 'json';
        $param['key']     = $this->applicationKey;
        $this->param      = http_build_query($param);

        if ($this->caching['enabled'] === true) {
          $this->caching['key'] = md5($this->param);

          if (Cache::has($this->caching['key'])) {
            return unserialize(Cache::get($this->caching['key']));
          }
        }

        return $this->call();
    }

}
