<?php
/**
 * EuroParcel - PrestaShop Shipping Module
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @version   1.0.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class EuroParcel extends Module
{
    private $carrier_name = 'EuroParcel - Home Delivery';
    private $carrier_locker_name = 'EuroParcel Locker Delivery';
    private $carrier_delay = 'Delivery in 24-48 hours';
    private $carrier_locker_delay = 'Next day delivery';
    private $available_lockers = [
        'easybox' => 'Sameday EasyBox - Delivery to locker',
        'fanbox' => 'Fan Courier FANbox - Delivery to locker',
        'dpdbox' => 'DPD Box - Delivery to locker',
        'carguslocker' => 'Cargus - Delivery to locker',
    ];
    private $available_carriers = [
        'cargus_national' => 'Cargus - Delivery to address',
        'dpd_standard' => 'DPD - Delivery to address',
        'fan_courier' => 'Fan Courier - Delivery to address',
        'gls_national' => 'GLS - Delivery to address',
        'sameday' => 'SameDay - Delivery to address',
        'bookurier' => 'Bookurier - Delivery to address',
    ];
    private $carrier_settings = [
        'cargus_national' => [
            'carrier' => 'cargus_national',
            'carrier_id' => 1,
            'service_id' => 1,
            'logo' => 'cargus.webp',
        ],
        'dpd_standard' => [
            'carrier' => 'dpd_standard',
            'carrier_id' => 2,
            'service_id' => 1,
            'logo' => 'dpd.webp',
        ],
        'fan_courier' => [
            'carrier' => 'fan_courier',
            'carrier_id' => 3,
            'service_id' => 1,
            'logo' => 'fan-courier.webp',
        ],
        'gls_national' => [
            'carrier' => 'gls_national',
            'carrier_id' => 4,
            'service_id' => 1,
            'logo' => 'gls.webp',
        ],
        'sameday' => [
            'carrier' => 'sameday',
            'carrier_id' => 6,
            'service_id' => 1,
            'logo' => 'sameday.webp',
        ],
        'bookurier' => [
            'carrier' => 'bookurier',
            'carrier_id' => 5,
            'service_id' => 1,
            'logo' => 'bookurier.webp',
        ],
        'easybox' => [
            'carrier' => 'easybox',
            'carrier_id' => 6,
            'service_id' => 2,
            'logo' => 'easybox.webp',
        ],
        'fanbox' => [
            'carrier' => 'fanbox',
            'carrier_id' => 3,
            'service_id' => 2,
            'logo' => 'fanbox.webp',
        ],
        'dpdbox' => [
            'carrier' => 'dpdbox',
            'carrier_id' => 2,
            'service_id' => 2,
            'logo' => 'dpd.webp',
        ],
        'carguslocker' => [
            'carrier' => 'carguslocker',
            'carrier_id' => 1,
            'service_id' => 2,
            'logo' => 'cargus.webp',
        ],
    ];

    public function __construct()
    {
        $this->name = 'europarcel';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'EuroParcel';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EuroParcel');
        $this->description = $this->l('Create and manage the EuroParcel carrier for home delivery and locker pickup');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('EUROPARCEL_CARRIER_ID')) {
            $this->warning = $this->l('No carrier ID found. Please reinstall the module.');
        }
    }

    public function install()
    {
        // Add the europarcel_locker_id field to the orders table
        if (!$this->addLockerIdField()) {
            return false;
        }
        if (!$this->addCarrierIdField()) {
            return false;
        }
        if (!$this->addServiceIdField()) {
            return false;
        }
        if (!$this->addCustomerLockerField()) {
            return false;
        }

        return parent::install()
                && $this->createEuroParcelCarrier()
                && $this->createEuroParcelCarrierLocker()
                && $this->registerHook('displayHeader')
                && $this->registerHook('displayCarrierExtraContent')
                && $this->registerHook('actionValidateOrder')
                && $this->registerHook('displayAdminOrderMain')
                && $this->registerHook('actionValidateStepComplete')
                && $this->registerHook('displayOrderConfirmation')
                && $this->registerHook('actionCarrierUpdate')
                && Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', '')
                && Configuration::updateValue('EUROPARCEL_DEFAULT_CARRIER', 'cargus_national');
    }

    public function uninstall()
    {
        $this->deleteEuroParcelCarrier();
        $this->deleteEuroParcelCarrierLocker();

        return Configuration::deleteByName('EUROPARCEL_CARRIER_ID')
                && Configuration::deleteByName('EUROPARCEL_LOCKER_CARRIER_ID')
                && Configuration::deleteByName('EUROPARCEL_LOCKER_TYPES')
                && Configuration::deleteByName('EUROPARCEL_DEFAULT_CARRIER')
                && parent::uninstall();
    }

    private function addLockerIdField()
    {
        // Check if the field already exists
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'orders`');
        $columnExists = false;

        if ($columns) {
            foreach ($columns as $column) {
                if ($column['Field'] === 'europarcel_locker_id') {
                    $columnExists = true;

                    break;
                }
            }
        }

        if (!$columnExists) {
            // Add the europarcel_locker_id field to the orders table
            $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'orders`
                    ADD `europarcel_locker_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`';

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Error adding europarcel_locker_id field to orders table');

                return false;
            }
        }

        return true;
    }

    private function addCarrierIdField()
    {
        // Check if the field already exists
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'orders`');
        $columnExists = false;

        if ($columns) {
            foreach ($columns as $column) {
                if ($column['Field'] === 'europarcel_carrier_id') {
                    $columnExists = true;

                    break;
                }
            }
        }

        if (!$columnExists) {
            // Add the europarcel_carrier_id field to the orders table
            $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'orders`
                    ADD `europarcel_carrier_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`';

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Error adding europarcel_carrier_id field to orders table');

                return false;
            }
        }

        return true;
    }

    private function addCustomerLockerField()
    {
        // Check if the field already exists
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'customer`');
        $columnExists = false;

        if ($columns) {
            foreach ($columns as $column) {
                if ($column['Field'] === 'europarcel_locker_data') {
                    $columnExists = true;

                    break;
                }
            }
        }

        if (!$columnExists) {
            // Add the europarcel_locker_data field to the customer table
            $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'customer`
                    ADD `europarcel_locker_data` TEXT NULL DEFAULT NULL';

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Error adding europarcel_locker_data field to customer table');

                return false;
            }
        }

        return true;
    }

    private function addServiceIdField()
    {
        // Check if the field already exists
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'orders`');
        $columnExists = false;

        if ($columns) {
            foreach ($columns as $column) {
                if ($column['Field'] === 'europarcel_service_id') {
                    $columnExists = true;

                    break;
                }
            }
        }

        if (!$columnExists) {
            // Add the europarcel_service_id field to the orders table
            $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'orders`
                    ADD `europarcel_service_id` INT UNSIGNED NULL DEFAULT NULL AFTER `europarcel_carrier_id`';

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Error adding europarcel_service_id field to orders table');

                return false;
            }
        }

        return true;
    }

    private function createEuroParcelCarrier()
    {
        $carrier = new Carrier();
        $carrier->name = $this->carrier_name;
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = false;

        // Set the delay for all languages
        $languages = Language::getLanguages(false);
        $delay = [];
        foreach ($languages as $language) {
            $delay[$language['id_lang']] = $this->carrier_delay;
        }
        $carrier->delay = $delay;

        $carrier->shipping_external = false;
        $carrier->is_module = true;
        $carrier->external_module_name = $this->name;
        $carrier->need_range = true;
        $carrier->max_width = 0;
        $carrier->max_height = 0;
        $carrier->max_depth = 0;
        $carrier->max_weight = 50;
        $carrier->grade = 0;

        // Add the carrier
        if (!$carrier->add()) {
            $this->_errors[] = $this->l('Error creating EuroParcel carrier');

            return false;
        }

        $carrier_id = (int) $carrier->id;

        // Save the carrier ID
        Configuration::updateValue('EUROPARCEL_CARRIER_ID', $carrier_id);

        // Add to all groups
        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            Db::getInstance()->insert('carrier_group', [
                'id_carrier' => $carrier_id,
                'id_group' => (int) $group['id_group'],
            ]);
        }

        // Create price ranges
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier_id;
        $rangePrice->delimiter1 = 0.0;
        $rangePrice->delimiter2 = 10000.0;
        if (!$rangePrice->add()) {
            $this->_errors[] = $this->l('Error creating price range');

            return false;
        }

        // Create weight ranges
        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier_id;
        $rangeWeight->delimiter1 = 0.0;
        $rangeWeight->delimiter2 = 10000.0;
        if (!$rangeWeight->add()) {
            $this->_errors[] = $this->l('Error creating weight range');

            return false;
        }

        // Add to all zones
        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert('carrier_zone', [
                'id_carrier' => $carrier_id,
                'id_zone' => (int) $zone['id_zone'],
            ]);

            // Add delivery price
            Db::getInstance()->insert('delivery', [
                'id_carrier' => $carrier_id,
                'id_range_price' => (int) $rangePrice->id,
                'id_range_weight' => (int) $rangeWeight->id,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '0.00',
            ]);
        }

        return true;
    }

    private function createEuroParcelCarrierLocker()
    {
        $carrier = new Carrier();
        $carrier->name = $this->carrier_locker_name;
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = false;

        // Set the delay for all languages
        $languages = Language::getLanguages(false);
        $delay = [];
        foreach ($languages as $language) {
            $delay[$language['id_lang']] = $this->carrier_locker_delay;
        }
        $carrier->delay = $delay;

        $carrier->shipping_external = false;
        $carrier->is_module = true;
        $carrier->external_module_name = $this->name;
        $carrier->need_range = true;
        $carrier->max_width = 60;
        $carrier->max_height = 60;
        $carrier->max_depth = 60;
        $carrier->max_weight = 20;
        $carrier->grade = 9;

        // Add the carrier
        if (!$carrier->add()) {
            $this->_errors[] = $this->l('Error creating EuroParcel Locker carrier');

            return false;
        }

        $carrier_id = (int) $carrier->id;

        // Save the carrier ID
        Configuration::updateValue('EUROPARCEL_LOCKER_CARRIER_ID', $carrier_id);

        // Add to all groups
        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            Db::getInstance()->insert('carrier_group', [
                'id_carrier' => $carrier_id,
                'id_group' => (int) $group['id_group'],
            ]);
        }

        // Create price ranges
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier_id;
        $rangePrice->delimiter1 = 0.0;
        $rangePrice->delimiter2 = 10000.0;
        if (!$rangePrice->add()) {
            $this->_errors[] = $this->l('Error creating price range for locker');

            return false;
        }

        // Create weight ranges
        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier_id;
        $rangeWeight->delimiter1 = 0.0;
        $rangeWeight->delimiter2 = 10000.0;
        if (!$rangeWeight->add()) {
            $this->_errors[] = $this->l('Error creating weight range for locker');

            return false;
        }

        // Add to all zones
        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert('carrier_zone', [
                'id_carrier' => $carrier_id,
                'id_zone' => (int) $zone['id_zone'],
            ]);

            // Add delivery price
            Db::getInstance()->insert('delivery', [
                'id_carrier' => $carrier_id,
                'id_range_price' => (int) $rangePrice->id,
                'id_range_weight' => (int) $rangeWeight->id,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '0.00',
            ]);
        }

        return true;
    }

    private function deleteEuroParcelCarrier()
    {
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        if ($carrier_id) {
            $carrier = new Carrier((int) $carrier_id);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = true;

                return $carrier->update();
            }
        }

        return true;
    }

    private function deleteEuroParcelCarrierLocker()
    {
        $carrier_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        if ($carrier_id) {
            $carrier = new Carrier((int) $carrier_id);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = true;

                return $carrier->update();
            }
        }

        return true;
    }

    public function hookDisplayHeader()
    {
        // Register JavaScripts
        $this->context->controller->registerJavascript(
            'europarcel-modal',
            $this->_path . 'views/js/europarcel-modal.js',
            ['position' => 'bottom', 'priority' => 80]
        );

        $this->context->controller->registerJavascript(
            'europarcel-checkout',
            $this->_path . 'views/js/europarcel-checkout.js',
            ['position' => 'bottom', 'priority' => 90]
        );
        // Pass AJAX URL and token to JavaScript
        Media::addJsDef([
            'europarcel_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'europarcel_token' => Tools::getToken(false),
        ]);
    }

    public function hookDisplayCarrierExtraContent($params)
    {
        $carrier = $params['carrier'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $carrier['id'] === $locker_carrier_id) {
            $address = new Address($this->context->cart->id_address_delivery);
            $country = new Country($address->id_country);
            $state = new State($address->id_state);
            // Get courier IDs from configuration
            $lockers_couriers = Configuration::get('EUROPARCEL_LOCKER_TYPES');
            if ($lockers_couriers) {
                $lockers_couriers_arr = explode(',', $lockers_couriers);
                $courierIdsArr = [];
                foreach ($lockers_couriers_arr as $locker_carier) {
                    $courierIdsArr[] = $this->carrier_settings[$locker_carier]['carrier_id'];
                }
                $courierIds = implode(',', $courierIdsArr);
            } else {
                $courierIds = ''; // Default
            }

            $europarcel_locker_data = $this->getSelectedLockerFromSession();
            if (!$europarcel_locker_data && $this->context->customer->id) {
                $customerLocker = $this->getCustomerLocker($this->context->customer->id);
                if ($customerLocker) {
                    $europarcel_locker_data = $customerLocker;
                    $this->context->cookie->__set('europarcel_locker_data', json_encode($customerLocker));
                    $this->context->cookie->write();
                }
            }
            $this->context->smarty->assign([
                'selected_locker' => $europarcel_locker_data,
                'europarcel_locker_data' => $europarcel_locker_data ? json_encode($europarcel_locker_data) : '',
                'checkout_data' => [
                    'address' => $address->address1 . ($address->address2 ? ', ' . $address->address2 : ''),
                    'city' => $address->city,
                    'state' => $state->iso_code,
                    'postcode' => $address->postcode,
                    'country' => $country->name,
                ],
                'courier_ids' => $courierIds,
                'module_dir' => $this->_path,
            ]);

            return $this->display(__FILE__, 'carrier_extra_content.tpl');
        }

        return '';
    }

    public function hookActionValidateStepComplete($params)
    {
        // Check if we are in the shipping step (step 2)
        if ($params['step_name'] !== 'delivery') {
            return true; // Let the other steps continue
        }

        $selectedCarrierId = (int) $this->context->cart->id_carrier;
        $lockerCarrierId = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ($lockerCarrierId == $selectedCarrierId) {
            $selectedCarrierData = $this->getSelectedLockerFromSession();

            if (!$selectedCarrierData) {
                // This will BLOCK progress to the next step
                $params['completed'] = false;
                $this->context->controller->errors[] = $this->l('Please select a EuroParcel locker before proceeding.');

                return false; // Important: return false to block
            }
        }

        return true; // Allow continuation
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        $carrier_id = (int) Configuration::get('EUROPARCEL_CARRIER_ID');
        $default_carrier = Configuration::get('EUROPARCEL_DEFAULT_CARRIER');
        if ((int) $order->id_carrier === $locker_carrier_id) {
            $selected_locker = $this->getSelectedLockerFromSession();

            if ($selected_locker && isset($selected_locker['id'])) {
                // Save the locker ID directly in orders
                $this->saveLockerToOrder($order->id, $selected_locker['id'], $selected_locker['carrier_id']);
                if ($this->context->customer->id) {
                    $this->saveCustomerLocker($this->context->customer->id, $selected_locker);
                }
            } else {
                $this->context->controller->errors[] = $this->l('Please select a EuroParcel locker before proceeding.');

                return false; // Important: return false to block
            }
        } elseif ((int) $order->id_carrier === $carrier_id) {
            $this->saveCarrierToOrder($order->id, $this->carrier_settings[$default_carrier]['carrier_id']);
        }
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $order->id_carrier === $locker_carrier_id) {
            $europarcel_locker_data = $this->getSelectedLockerFromSession();

            if ($europarcel_locker_data) {
                $this->context->smarty->assign([
                    'locker_info' => $europarcel_locker_data,
                ]);
                $this->clearLockerFromSession();

                return $this->display(__FILE__, 'order_confirmation_locker.tpl');
            }
        }
        $this->clearLockerFromSession();

        return '';
    }

    public function hookActionCarrierUpdate($params)
    {
        // This hook is called when a carrier is updated in the backend
        $id_carrier_old = (int) $params['id_carrier'];
        $id_carrier_new = (int) $params['carrier']->id;

        // Example: update the EuroParcel carrier ID in configuration
        $currentEuroparcelCarrierId = Configuration::get('EUROPARCEL_CARRIER_ID');
        $currentEuroparcelLockerCarrierId = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        if ($currentEuroparcelCarrierId == $id_carrier_old) {
            // The EuroParcel carrier has been updated, save the new ID
            Configuration::updateValue('EUROPARCEL_CARRIER_ID', $id_carrier_new);
        }
        if ($currentEuroparcelLockerCarrierId == $id_carrier_old) {
            // The EuroParcel carrier has been updated, save the new ID
            Configuration::updateValue('EUROPARCEL_LOCKER_CARRIER_ID', $id_carrier_new);
        }
    }

    public function hookDisplayAdminOrderMain($params)
    {
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $order->id_carrier === $locker_carrier_id) {
            $europarcel_locker_id = $this->getLockerIdFromOrder($order->id);

            if ($europarcel_locker_id) {
                $this->context->smarty->assign([
                    'europarcel_locker_id' => $europarcel_locker_id,
                ]);

                return $this->display(__FILE__, 'displayAdminOrder.tpl');
            }
        }

        return '';
    }

    private function saveLockerToOrder($order_id, $europarcel_locker_id, $europarcel_carrier_id)
    {
        // Save directly to the orders table
        return Db::getInstance()->update(
            'orders',
            [
                            'europarcel_locker_id' => (int) $europarcel_locker_id,
                            'europarcel_service_id' => 2,
                            'europarcel_carrier_id' => (int) $europarcel_carrier_id,
                        ],
            'id_order = ' . (int) $order_id
        );
    }

    private function saveCarrierToOrder($order_id, $europarcel_carrier_id)
    {
        // Save directly to the orders table
        return Db::getInstance()->update(
            'orders',
            [
                            'europarcel_service_id' => 1,
                            'europarcel_carrier_id' => (int) $europarcel_carrier_id,
                        ],
            'id_order = ' . (int) $order_id
        );
    }

    private function getLockerIdFromOrder($order_id)
    {
        $sql = new DbQuery();
        $sql->select('europarcel_locker_id')
                ->from('orders')
                ->where('id_order = ' . (int) $order_id);

        return Db::getInstance()->getValue($sql);
    }

    private function getSelectedLockerFromSession()
    {
        if (isset($this->context->cookie->europarcel_locker_data)) {
            return json_decode($this->context->cookie->europarcel_locker_data, true);
        }

        return null;
    }

    private function clearLockerFromSession()
    {
        unset($this->context->cookie->europarcel_locker_data);
    }

    public function getAvailableLockersForSelection()
    {
        $selected_lockers = explode(',', Configuration::get('EUROPARCEL_LOCKER_TYPES'));
        $available_lockers = [];

        foreach ($this->available_lockers as $key => $label) {
            if (in_array($key, $selected_lockers)) {
                $available_lockers[$key] = $label;
            }
        }

        return $available_lockers;
    }

    /**
     * Get carrier logo path
     *
     * @param string $carrier_key Carrier key (e.g., 'cargus_national', 'easybox')
     *
     * @return string Logo file name or empty string if not found
     */
    public function getCarrierLogo($carrier_key)
    {
        if (isset($this->carrier_settings[$carrier_key]['logo'])) {
            return $this->carrier_settings[$carrier_key]['logo'];
        }

        return '';
    }

    protected function saveCustomerLocker($id_customer, $lockerData)
    {
        $lockerDataJson = json_encode($lockerData);

        return Db::getInstance()->update(
            'customer',
            ['europarcel_locker_data' => pSQL($lockerDataJson)],
            'id_customer = ' . (int) $id_customer
        );
    }

    /**
     * Get the customer's preferred locker
     */
    protected function getCustomerLocker($id_customer)
    {
        $sql = new DbQuery();
        $sql->select('europarcel_locker_data')
                ->from('customer')
                ->where('id_customer = ' . (int) $id_customer);

        $result = Db::getInstance()->getValue($sql);

        if ($result) {
            $lockerData = json_decode($result, true);

            return $lockerData ?: null;
        }

        return null;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('save_europarcel_settings')) {
            $locker_types = Tools::getValue('EUROPARCEL_LOCKER_TYPES');
            $default_carrier = Tools::getValue('EUROPARCEL_DEFAULT_CARRIER');
            // Save the selected locker types
            if (is_array($locker_types)) {
                Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', implode(',', $locker_types));
            } else {
                Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', '');
            }
            if (!empty($default_carrier) && array_key_exists($default_carrier, $this->available_carriers)) {
                Configuration::updateValue('EUROPARCEL_DEFAULT_CARRIER', $default_carrier);
            }
            $output .= $this->displayConfirmation('Settings have been saved successfully.');
        }
        // Prepare data for template
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        $locker_carrier_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        $selected_lockers = explode(',', Configuration::get('EUROPARCEL_LOCKER_TYPES'));
        $default_carrier = Configuration::get('EUROPARCEL_DEFAULT_CARRIER');
        $carriers = [];
        foreach ($this->available_carriers as $key => $label) {
            $carriers[] = [ // Use [] to append, not ['key']
                'key' => $key,
                'name' => $label,
            ];
        }
        $carrier_logos = [];
        $available_lockers = [];
        foreach ($this->available_lockers as $key => $label) {
            $available_lockers[] = [ // Use [] to append, not ['key']
                'key' => $key,
                'name' => $label,
            ];
            $carrier_logos[$key] = $this->getCarrierLogo($key);
        }
        // Assign variables for template
        $this->context->smarty->assign([
            'post_url' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
            'carriers' => $carriers,
            'default_carrier' => $default_carrier,
            'available_lockers' => $available_lockers,
            'selected_lockers' => $selected_lockers,
            'carrier_logos' => $carrier_logos,
            'module_path' => $this->context->shop->getBaseURL(true) . 'modules/' . $this->name . '/',
            'carrier_id' => $carrier_id,
            'locker_carrier_id' => $locker_carrier_id,
        ]);
        $output .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl');

        return $output;
    }
}
