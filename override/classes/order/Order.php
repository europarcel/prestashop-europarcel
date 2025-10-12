<?php

class Order extends OrderCore {

    public $locker_id;

    public function __construct($id = null) {
        self::$definition['fields']['europarcel_locker_id'] = array(
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false
        );
        self::$definition['fields']['europarcel_carrier_id'] = array(
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false
        );
         self::$definition['fields']['europarcel_service_id'] = array(
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false
        );
        parent::__construct($id);
    }

    public function getWebserviceParameters($ws_params_attribute_name = null) {
        $params = parent::getWebserviceParameters($ws_params_attribute_name);
        $params['fields']['europarcel_locker_id'] = array(
            'sqlId' => 'europarcel_locker_id',
            'i18n' => false
        );
        $params['fields']['europarcel_carrier_id'] = array(
            'sqlId' => 'europarcel_carrier_id',
            'i18n' => false
        );
        $params['fields']['europarcel_service_id'] = array(
            'sqlId' => 'europarcel_service_id',
            'i18n' => false
        );
        return $params;
    }
}
