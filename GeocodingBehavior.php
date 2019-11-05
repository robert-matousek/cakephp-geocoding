<?php

/*

 * Author: Robert Matousek

 * Date: 28/09/2011

 * Contact: r.k.matousek@gmail.com

 * Description: CakePHP geocoding behavior retrieves coordinates using Google Geocoding API (V3).

 */



App::uses('HttpSocket', 'Network/Http');



class GeocodingBehavior extends ModelBehavior {



    const OK               = 'OK';                // no errors occurred

    const ZERO_RESULTS     = 'ZERO_RESULTS';      // geocode was successful but returned no results

    const OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';  // you are over your quota

    const REQUEST_DENIED   = 'REQUEST_DENIED';    // your request was denied

    const INVALID_REQUEST  = 'INVALID_REQUEST';   // the query (address or latlng) is missing.

 

    /**

     * Model-specific settings

     * @var array

     */

    public $settings = array();



    /**

     * Geocoding API

     * @var string

     * @link http://code.google.com/apis/maps/documentation/geocoding/

     */                

    private $uri = 'http://maps.google.com/maps/api/geocode/json';



    /**

     * parameters

     * @var string

     */                

    private $parameters = array(

      'address' => null,

      'sensor' => 'false'

    );



    /**

     * Setup

     * @param  $model

     * @param  $settings

     */

    public function setup(&$Model, $settings) {

          //default settings

          if (!isset($this->settings[$Model->alias])) {

              $this->settings[$Model->alias] = array(

                  'address'   => 'address',

                  'lat_field' => 'lat',

                  'lng_field' => 'lng'

              );

          }           

          $this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);

      } 



      public function beforeSave(&$Model){

          $address = $this->settings[$Model->alias]['address'];                    

          $response = $this->_lookup($Model->data[$Model->alias][$address]);

          $jsonO = json_decode($response);

           

          //check the response by status code

          if($this->_isOk($jsonO->status)) {

              $lat = $this->settings[$Model->alias]['lat_field'];

              $lng = $this->settings[$Model->alias]['lng_field'];            

              $Model->data[$Model->alias][$lat] = $jsonO->results[0]->geometry->location->lat;

              $Model->data[$Model->alias][$lng] = $jsonO->results[0]->geometry->location->lng;

              return true;

          }

            else {

              $Model->data[$Model->alias]['error'] = $jsonO->status;

              return false;

          }

      }

   

      /**

       * Check Geocoding response

       * @param $response JSON response

       */        

      private function _isOk($response){

          switch($response) {

                  case self::OK:

                      return true;

                      break;

                  case self::ZERO_RESULTS:

                      $this->error = true;

                      return false;

                      break;

                  case self::OVER_QUERY_LIMIT:

                      $this->error = true;

                      return false;

                      break;

                  case self::REQUEST_DENIED:

                      $this->error = true;

                      return false;

                      break;

                  case self::INVALID_REQUEST:

                      $this->error = true;

                      return false;

                      break;

          }

      }

   

    /**

     * Format address for Geocoding

     * @param  $address the address to geocode

     */ 

    public function _setAddress($address = null) {

      $this->parameters['address'] = str_replace(' ', '+', $address);

    }

 

    /**

     * Query Google Geocoding API

     * @param  $address the address to geocode

     */

      private function _lookup($address = null){

          $this->_setAddress($address);       

          $http = new HttpSocket();

          $result = $http->get($this->uri, $this->parameters);

          return $result;

      }



}

?>