<?php

/**
 * EuroParcel Order Override
 *
 * Extends PrestaShop Order class to include EuroParcel locker fields.
 * EuroParcel reads these fields through the PrestaShop webservice.
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Order extends OrderCore
{
    /** @var int|null EuroParcel locker ID */
    public $europarcel_locker_id;

    /** @var int|null EuroParcel courier ID */
    public $europarcel_carrier_id;

    /** @var int|null EuroParcel service ID: 1 home delivery, 2 locker delivery */
    public $europarcel_service_id;

    public function __construct($id = null, $id_lang = null)
    {
        self::$definition['fields']['europarcel_locker_id'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false,
        ];
        self::$definition['fields']['europarcel_carrier_id'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false,
        ];
        self::$definition['fields']['europarcel_service_id'] = [
            'type' => self::TYPE_INT,
            'validate' => 'isUnsignedId',
            'required' => false,
        ];
        parent::__construct($id, $id_lang);
    }

    /**
     * Expose the EuroParcel fields in the orders webservice resource.
     *
     * @param string|null $ws_params_attribute_name
     *
     * @return array<string, mixed>
     */
    public function getWebserviceParameters($ws_params_attribute_name = null)
    {
        $params = parent::getWebserviceParameters($ws_params_attribute_name);
        $params['fields']['europarcel_locker_id'] = [
            'sqlId' => 'europarcel_locker_id',
            'i18n' => false,
        ];
        $params['fields']['europarcel_carrier_id'] = [
            'sqlId' => 'europarcel_carrier_id',
            'i18n' => false,
        ];
        $params['fields']['europarcel_service_id'] = [
            'sqlId' => 'europarcel_service_id',
            'i18n' => false,
        ];

        return $params;
    }
}
