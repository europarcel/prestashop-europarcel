<?php

/**
 * EuroParcel - PrestaShop Shipping Module
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @version   2.0.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @phpstan-type Locker array{id: int, carrier_id: int, name: string, address: string, carrier_name: string}
 * @phpstan-type Service array{carrier_id: int, service_id: int, logo: string}
 */
class EuroParcel extends Module
{
    /**
     * EuroParcel service ids. They are stored on orders and read by EuroParcel through the webservice.
     */
    public const SERVICE_HOME = 1;
    public const SERVICE_LOCKER = 2;

    private const CONFIG_HOME_CARRIER_ID = 'EUROPARCEL_CARRIER_ID';
    private const CONFIG_LOCKER_CARRIER_ID = 'EUROPARCEL_LOCKER_CARRIER_ID';
    private const CONFIG_LOCKER_TYPES = 'EUROPARCEL_LOCKER_TYPES';
    private const CONFIG_DEFAULT_CARRIER = 'EUROPARCEL_DEFAULT_CARRIER';

    /**
     * Cookie key of the locker picked for the current checkout.
     */
    private const COOKIE_LOCKER = 'europarcel_locker_data';

    /**
     * EuroParcel endpoint that answers whether a locker is still in service.
     */
    private const FIXED_LOCATION_URL = 'https://api.europarcel.com/api/public/locations/fixedlocations/';

    private const TRACKING_URL = 'https://www.eawb.ro/tracking?awb=@';

    private const HOOKS = [
        'displayHeader',
        'displayCarrierExtraContent',
        'actionValidateStepComplete',
        'actionValidateOrder',
        'displayOrderConfirmation',
        'displayAdminOrderMain',
        'actionCarrierUpdate',
    ];

    /**
     * The two PrestaShop carriers the module creates, keyed by the configuration key that stores their id.
     */
    private const CARRIERS = [
        self::CONFIG_HOME_CARRIER_ID => [
            'name' => 'EuroParcel - Home Delivery',
            'delay' => 'Delivery in 24-48 hours',
            'grade' => 0,
            'max_width' => 0,
            'max_height' => 0,
            'max_depth' => 0,
            'max_weight' => 50,
        ],
        self::CONFIG_LOCKER_CARRIER_ID => [
            'name' => 'EuroParcel Locker Delivery',
            'delay' => 'Next day delivery',
            'grade' => 9,
            'max_width' => 60,
            'max_height' => 60,
            'max_depth' => 60,
            'max_weight' => 20,
        ],
    ];

    /**
     * EuroParcel services the merchant can offer, with their EuroParcel courier id (carrier_id), service
     * and logo. Their labels are in getServiceLabels(), so they can be translated.
     */
    private const SERVICES = [
        'cargus_national' => ['carrier_id' => 1, 'service_id' => self::SERVICE_HOME, 'logo' => 'cargus.webp'],
        'dpd_standard' => ['carrier_id' => 2, 'service_id' => self::SERVICE_HOME, 'logo' => 'dpd.webp'],
        'fan_courier' => ['carrier_id' => 3, 'service_id' => self::SERVICE_HOME, 'logo' => 'fan-courier.webp'],
        'gls_national' => ['carrier_id' => 4, 'service_id' => self::SERVICE_HOME, 'logo' => 'gls.webp'],
        'sameday' => ['carrier_id' => 6, 'service_id' => self::SERVICE_HOME, 'logo' => 'sameday.webp'],
        'bookurier' => ['carrier_id' => 5, 'service_id' => self::SERVICE_HOME, 'logo' => 'bookurier.webp'],
        'easybox' => ['carrier_id' => 6, 'service_id' => self::SERVICE_LOCKER, 'logo' => 'easybox.webp'],
        'fanbox' => ['carrier_id' => 3, 'service_id' => self::SERVICE_LOCKER, 'logo' => 'fanbox.webp'],
        'dpdbox' => ['carrier_id' => 2, 'service_id' => self::SERVICE_LOCKER, 'logo' => 'dpd.webp'],
        'carguslocker' => ['carrier_id' => 1, 'service_id' => self::SERVICE_LOCKER, 'logo' => 'cargus.webp'],
    ];

    private const DEFAULT_HOME_SERVICE = 'cargus_national';

    public function __construct()
    {
        $this->name = 'europarcel';
        $this->tab = 'shipping_logistics';
        $this->version = '2.0.0';
        $this->author = 'EuroParcel';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '9.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EuroParcel');
        $this->description = $this->l('Create and manage the EuroParcel carrier for home delivery and locker pickup');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if ($this->id && !Configuration::get(self::CONFIG_HOME_CARRIER_ID)) {
            $this->warning = $this->l('No carrier ID found. Please reinstall the module.');
        }
    }

    /**
     * @return bool
     */
    public function install()
    {
        // The columns are kept on uninstall, so they are added first and only when missing.
        return $this->installDatabase()
            && parent::install()
            && $this->installCarriers()
            && $this->registerHook(self::HOOKS)
            && Configuration::updateValue(self::CONFIG_LOCKER_TYPES, '')
            && Configuration::updateValue(self::CONFIG_DEFAULT_CARRIER, self::DEFAULT_HOME_SERVICE);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        // The carriers are only flagged as deleted, because past orders still use them.
        $this->deleteCarrier(self::CONFIG_HOME_CARRIER_ID);
        $this->deleteCarrier(self::CONFIG_LOCKER_CARRIER_ID);

        foreach ([self::CONFIG_HOME_CARRIER_ID, self::CONFIG_LOCKER_CARRIER_ID, self::CONFIG_LOCKER_TYPES, self::CONFIG_DEFAULT_CARRIER] as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    /**
     * Load the locker picker. It is loaded on every page, not only on the order page, so it also works
     * with one-page checkout modules that show the carriers on their own page.
     */
    public function hookDisplayHeader(): void
    {
        $controller = $this->context->controller;
        $controller->registerStylesheet('europarcel-style', 'modules/' . $this->name . '/views/css/europarcel.css', ['media' => 'all', 'priority' => 150]);
        $controller->registerJavascript('europarcel-modal', 'modules/' . $this->name . '/views/js/europarcel-modal.js', ['position' => 'bottom', 'priority' => 80]);
        $controller->registerJavascript('europarcel-checkout', 'modules/' . $this->name . '/views/js/europarcel-checkout.js', ['position' => 'bottom', 'priority' => 90]);

        Media::addJsDef([
            'europarcel_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'europarcel_token' => Tools::getToken(false),
        ]);
    }

    /**
     * Show the locker picker under the locker carrier in the checkout.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayCarrierExtraContent(array $params)
    {
        if (!isset($params['carrier']['id']) || !$this->isLockerCarrier($params['carrier']['id'])) {
            return '';
        }

        $address = new Address((int) $this->context->cart->id_address_delivery);
        $state = new State((int) $address->id_state);

        $this->smarty->assign([
            'europarcel_locker' => $this->getUsableLocker(),
            'europarcel_city' => (string) $address->city,
            'europarcel_county' => (string) $state->iso_code,
            'europarcel_courier_ids' => implode(',', $this->getLockerCourierIds()),
        ]);

        return $this->display(__FILE__, 'carrier_extra_content.tpl');
    }

    /**
     * Keep the customer on the delivery step until a locker is picked for the locker carrier.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionValidateStepComplete(array $params): void
    {
        if (!isset($params['step_name']) || $params['step_name'] !== 'delivery'
            || !$this->isLockerCarrier($this->context->cart->id_carrier)) {
            return;
        }

        $locker = $this->getUsableLocker();

        if (!$locker) {
            // 'completed' is a reference to the checkout step's state.
            $params['completed'] = false;
            $this->context->controller->errors[] = $this->l('Please select a EuroParcel locker before proceeding.');

            return;
        }

        // Payment notifications (IPN, webhooks) can create the order without the customer's cookie,
        // so the locker is also kept on the customer for hookActionValidateOrder().
        $this->saveCustomerLocker($this->context->customer->id, $locker);
    }

    /**
     * Store the EuroParcel service, courier and locker on a new order.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionValidateOrder(array $params): void
    {
        if (!isset($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return;
        }

        $order = $params['order'];

        if ($this->isLockerCarrier($order->id_carrier)) {
            $locker = $this->getSelectedLockerFromSession();

            if (!$locker) {
                $locker = $this->getCustomerLocker($order->id_customer);
            }

            if (!$locker) {
                PrestaShopLogger::addLog(
                    sprintf('EuroParcel: locker order %d was placed without a locker.', (int) $order->id),
                    3,
                    null,
                    'Order',
                    (int) $order->id,
                    true
                );

                return;
            }

            $this->saveOrderService($order, self::SERVICE_LOCKER, $locker['carrier_id'], $locker['id']);
            $this->saveCustomerLocker($order->id_customer, $locker);
        } elseif ($this->isHomeCarrier($order->id_carrier)) {
            $service = $this->getDefaultHomeService();
            $this->saveOrderService($order, self::SERVICE_HOME, $service ? $service['carrier_id'] : null, null);
        }
    }

    /**
     * Show the picked locker on the order confirmation page, then end the checkout's locker selection.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        $locker = $this->getSelectedLockerFromSession();
        $this->clearLockerFromSession();

        if (!$locker || !isset($params['order']) || !$this->isLockerCarrier($params['order']->id_carrier)) {
            return '';
        }

        $this->smarty->assign(['europarcel_locker' => $locker]);

        return $this->display(__FILE__, 'order_confirmation_locker.tpl');
    }

    /**
     * Keep the carrier ids up to date: PrestaShop saves an edited carrier under a new id.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionCarrierUpdate(array $params): void
    {
        if (!isset($params['id_carrier'], $params['carrier']) || !Validate::isLoadedObject($params['carrier'])) {
            return;
        }

        foreach ([self::CONFIG_HOME_CARRIER_ID, self::CONFIG_LOCKER_CARRIER_ID] as $key) {
            if ((int) Configuration::get($key) === (int) $params['id_carrier']) {
                Configuration::updateValue($key, (int) $params['carrier']->id);
            }
        }
    }

    /**
     * Show the EuroParcel service of an order on the back office order page.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderMain(array $params)
    {
        $order = new Order(isset($params['id_order']) ? (int) $params['id_order'] : 0);

        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $isLockerOrder = $this->isLockerCarrier($order->id_carrier);

        if (!$isLockerOrder && !$this->isHomeCarrier($order->id_carrier)) {
            return '';
        }

        $data = $this->getOrderServiceData($order->id);
        $serviceId = (int) $data['europarcel_service_id'];

        if ($serviceId !== self::SERVICE_HOME && $serviceId !== self::SERVICE_LOCKER) {
            $serviceId = $isLockerOrder ? self::SERVICE_LOCKER : self::SERVICE_HOME;
        }

        $this->smarty->assign([
            'europarcel_courier' => $this->getServiceLabel($data['europarcel_carrier_id'], $serviceId),
            'europarcel_is_locker_service' => $serviceId === self::SERVICE_LOCKER,
            'europarcel_locker_id' => (int) $data['europarcel_locker_id'],
        ]);

        return $this->display(__FILE__, 'displayAdminOrder.tpl');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('save_europarcel_settings')) {
            $output .= $this->saveSettings();
        }

        $this->context->controller->addCSS($this->_path . 'views/css/admin.css', 'all');

        return $output . $this->renderSettingsForm();
    }

    /**
     * Remember the locker the customer picked on the EuroParcel map for this checkout.
     *
     * @param mixed $data Locker data sent by the map
     *
     * @return bool False when the data does not describe a locker
     */
    public function saveSelectedLocker($data)
    {
        $locker = $this->normalizeLocker($data);

        if (!$locker) {
            return false;
        }

        $this->context->cookie->__set(self::COOKIE_LOCKER, $this->encodeLocker($locker));
        $this->context->cookie->write();

        return true;
    }

    /**
     * Save the settings form.
     *
     * @return string Confirmation or error message
     */
    private function saveSettings()
    {
        $defaultCarrier = (string) Tools::getValue(self::CONFIG_DEFAULT_CARRIER);
        $postedLockerTypes = Tools::getValue(self::CONFIG_LOCKER_TYPES);
        $lockerTypes = is_array($postedLockerTypes)
            ? array_values(array_intersect(array_keys($this->getServicesOfType(self::SERVICE_LOCKER)), $postedLockerTypes))
            : [];

        if (!array_key_exists($defaultCarrier, $this->getServicesOfType(self::SERVICE_HOME))) {
            return $this->displayError($this->l('Please choose the home delivery carrier.'));
        }

        Configuration::updateValue(self::CONFIG_DEFAULT_CARRIER, $defaultCarrier);
        Configuration::updateValue(self::CONFIG_LOCKER_TYPES, implode(',', $lockerTypes));

        return $this->displayConfirmation($this->l('Settings have been saved successfully.'));
    }

    /**
     * @return string
     */
    private function renderSettingsForm()
    {
        $labels = $this->getServiceLabels();
        $defaultCarrier = Configuration::get(self::CONFIG_DEFAULT_CARRIER);
        $selectedLockerTypes = $this->getSelectedLockerTypes();

        $homeServices = [];
        foreach (array_keys($this->getServicesOfType(self::SERVICE_HOME)) as $key) {
            $homeServices[] = [
                'key' => $key,
                'label' => $labels[$key],
                'selected' => $key === $defaultCarrier,
            ];
        }

        $lockerServices = [];
        foreach ($this->getServicesOfType(self::SERVICE_LOCKER) as $key => $service) {
            $lockerServices[] = [
                'key' => $key,
                'label' => $labels[$key],
                'logo' => $this->_path . 'views/img/' . $service['logo'],
                'checked' => in_array($key, $selectedLockerTypes, true),
            ];
        }

        $this->smarty->assign([
            'europarcel_form_action' => AdminController::$currentIndex . '&configure=' . $this->name
                . '&tab_module=' . $this->tab . '&module_name=' . $this->name
                . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'europarcel_home_services' => $homeServices,
            'europarcel_locker_services' => $lockerServices,
            'europarcel_home_carrier_id' => (int) Configuration::get(self::CONFIG_HOME_CARRIER_ID),
            'europarcel_locker_carrier_id' => (int) Configuration::get(self::CONFIG_LOCKER_CARRIER_ID),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }

    /**
     * Add the EuroParcel columns to the orders and customer tables.
     *
     * @return bool
     */
    private function installDatabase()
    {
        return $this->addColumnIfMissing('orders', 'europarcel_locker_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`')
            && $this->addColumnIfMissing('orders', 'europarcel_carrier_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`')
            && $this->addColumnIfMissing('orders', 'europarcel_service_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER `europarcel_carrier_id`')
            && $this->addColumnIfMissing('customer', 'europarcel_locker_data', 'TEXT NULL DEFAULT NULL');
    }

    /**
     * @param string $table Table name without prefix
     * @param string $column
     * @param string $definition SQL column definition
     *
     * @return bool
     */
    private function addColumnIfMissing($table, $column, $definition)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . $table;

        if ($db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '` WHERE Field = \'' . pSQL($column) . '\'')) {
            return true;
        }

        if ($db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD `' . bqSQL($column) . '` ' . $definition)) {
            return true;
        }

        $this->_errors[] = sprintf($this->l('Could not add the %1$s column to the %2$s table.'), $column, $table);

        return false;
    }

    /**
     * @return bool
     */
    private function installCarriers()
    {
        foreach (self::CARRIERS as $configKey => $definition) {
            if (!$this->createCarrier($configKey, $definition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a carrier handled by the module, open to every customer group and active zone, with a price
     * range and a weight range priced at 0 for the merchant to adjust.
     *
     * @param string $configKey Configuration key that stores the carrier id
     * @param array<string, int|string> $definition One of self::CARRIERS
     *
     * @return bool
     */
    private function createCarrier($configKey, array $definition)
    {
        $carrier = new Carrier();
        $carrier->name = $definition['name'];
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->is_module = true;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_external = false;
        $carrier->need_range = true;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = false;
        $carrier->grade = $definition['grade'];
        $carrier->max_width = $definition['max_width'];
        $carrier->max_height = $definition['max_height'];
        $carrier->max_depth = $definition['max_depth'];
        $carrier->max_weight = $definition['max_weight'];
        $carrier->url = self::TRACKING_URL;

        $delay = [];
        foreach (Language::getLanguages(false) as $language) {
            $delay[(int) $language['id_lang']] = $definition['delay'];
        }
        $carrier->delay = $delay;

        if (!$carrier->add()) {
            $this->_errors[] = sprintf($this->l('Could not create the %s carrier.'), $definition['name']);

            return false;
        }

        Configuration::updateValue($configKey, (int) $carrier->id);
        $this->copyCarrierLogo($carrier->id);

        $groupIds = [];
        foreach (Group::getGroups((int) $this->context->language->id) as $group) {
            $groupIds[] = (int) $group['id_group'];
        }
        $carrier->setGroups($groupIds);

        foreach ([new RangePrice(), new RangeWeight()] as $range) {
            $range->id_carrier = (int) $carrier->id;
            $range->delimiter1 = 0.0;
            $range->delimiter2 = 10000.0;

            if (!$range->add()) {
                $this->_errors[] = sprintf($this->l('Could not create the price ranges of the %s carrier.'), $definition['name']);

                return false;
            }
        }

        // Zones come after the ranges: adding a zone also adds its delivery prices for every range.
        foreach (Zone::getZones(true) as $zone) {
            $carrier->addZone((int) $zone['id_zone']);
        }

        return true;
    }

    /**
     * Use the module logo as the carrier logo.
     *
     * @param int $idCarrier
     *
     * @return bool
     */
    private function copyCarrierLogo($idCarrier)
    {
        $source = __DIR__ . '/logo.png';
        $destination = _PS_SHIP_IMG_DIR_ . (int) $idCarrier . '.jpg';

        if (!file_exists($source) || !copy($source, $destination)) {
            return false;
        }

        @chmod($destination, 0644);

        return true;
    }

    /**
     * @param string $configKey Configuration key that stores the carrier id
     *
     * @return bool
     */
    private function deleteCarrier($configKey)
    {
        $carrier = new Carrier((int) Configuration::get($configKey));

        if (!Validate::isLoadedObject($carrier)) {
            return true;
        }

        $carrier->deleted = true;

        return $carrier->update();
    }

    /**
     * @param int $idCarrier
     *
     * @return bool
     */
    private function isLockerCarrier($idCarrier)
    {
        return $this->isModuleCarrier($idCarrier, self::CONFIG_LOCKER_CARRIER_ID);
    }

    /**
     * @param int $idCarrier
     *
     * @return bool
     */
    private function isHomeCarrier($idCarrier)
    {
        return $this->isModuleCarrier($idCarrier, self::CONFIG_HOME_CARRIER_ID);
    }

    /**
     * Whether a carrier is the module carrier stored under a configuration key. Editing a carrier saves it
     * under a new id and keeps the old one for past orders; every version shares the same id_reference.
     *
     * @param int $idCarrier
     * @param string $configKey
     *
     * @return bool
     */
    private function isModuleCarrier($idCarrier, $configKey)
    {
        $idCarrier = (int) $idCarrier;
        $configuredId = (int) Configuration::get($configKey);

        if (!$idCarrier || !$configuredId) {
            return false;
        }

        if ($idCarrier === $configuredId) {
            return true;
        }

        $reference = $this->getCarrierReference($idCarrier);

        return $reference > 0 && $reference === $this->getCarrierReference($configuredId);
    }

    /**
     * @param int $idCarrier
     *
     * @return int The carrier's id_reference, or 0 when the carrier does not exist
     */
    private function getCarrierReference($idCarrier)
    {
        $carrier = new Carrier((int) $idCarrier);

        return Validate::isLoadedObject($carrier) ? (int) $carrier->id_reference : 0;
    }

    /**
     * @param int $serviceId self::SERVICE_HOME or self::SERVICE_LOCKER
     *
     * @return array<string, Service> Services of self::SERVICES offering that service, by key
     */
    private function getServicesOfType($serviceId)
    {
        $services = [];

        foreach (self::SERVICES as $key => $service) {
            if ($service['service_id'] === $serviceId) {
                $services[$key] = $service;
            }
        }

        return $services;
    }

    /**
     * @return array<string, string> Translated label of each service of self::SERVICES
     */
    private function getServiceLabels()
    {
        return [
            'cargus_national' => $this->l('Cargus - Delivery to address'),
            'dpd_standard' => $this->l('DPD - Delivery to address'),
            'fan_courier' => $this->l('Fan Courier - Delivery to address'),
            'gls_national' => $this->l('GLS - Delivery to address'),
            'sameday' => $this->l('SameDay - Delivery to address'),
            'bookurier' => $this->l('Bookurier - Delivery to address'),
            'easybox' => $this->l('Sameday EasyBox - Delivery to locker'),
            'fanbox' => $this->l('Fan Courier FANbox - Delivery to locker'),
            'dpdbox' => $this->l('DPD Box - Delivery to locker'),
            'carguslocker' => $this->l('Cargus - Delivery to locker'),
        ];
    }

    /**
     * @param int $courierId EuroParcel courier id
     * @param int $serviceId
     *
     * @return string Label of the matching service, or an empty string
     */
    private function getServiceLabel($courierId, $serviceId)
    {
        foreach (self::SERVICES as $key => $service) {
            if ($service['carrier_id'] === (int) $courierId && $service['service_id'] === (int) $serviceId) {
                $labels = $this->getServiceLabels();

                return $labels[$key];
            }
        }

        return '';
    }

    /**
     * @return Service|null The home delivery service picked in the settings
     */
    private function getDefaultHomeService()
    {
        $services = $this->getServicesOfType(self::SERVICE_HOME);
        $key = Configuration::get(self::CONFIG_DEFAULT_CARRIER);

        return isset($services[$key]) ? $services[$key] : null;
    }

    /**
     * @return string[] Keys of the locker networks offered to customers
     */
    private function getSelectedLockerTypes()
    {
        $lockerServices = $this->getServicesOfType(self::SERVICE_LOCKER);
        $selected = explode(',', (string) Configuration::get(self::CONFIG_LOCKER_TYPES));

        return array_values(array_intersect(array_keys($lockerServices), $selected));
    }

    /**
     * @return int[] EuroParcel courier ids of the locker networks offered to customers
     */
    private function getLockerCourierIds()
    {
        $ids = [];

        foreach ($this->getSelectedLockerTypes() as $key) {
            $ids[] = self::SERVICES[$key]['carrier_id'];
        }

        return $ids;
    }

    /**
     * Store the EuroParcel service on an order. It is written to the database and to the order object:
     * PrestaShop saves that same object again right after hookActionValidateOrder(), and when the Order
     * override's fields are loaded, that save writes them from the object.
     *
     * @param Order $order
     * @param int $serviceId
     * @param int|null $courierId EuroParcel courier id
     * @param int|null $lockerId
     *
     * @return bool
     */
    private function saveOrderService(Order $order, $serviceId, $courierId, $lockerId)
    {
        $fields = [
            'europarcel_service_id' => (int) $serviceId,
            'europarcel_carrier_id' => $courierId ? (int) $courierId : null,
            'europarcel_locker_id' => $lockerId ? (int) $lockerId : null,
        ];

        foreach ($fields as $field => $value) {
            if (property_exists($order, $field)) {
                $order->{$field} = $value;
            }
        }

        return Db::getInstance()->update('orders', $fields, 'id_order = ' . (int) $order->id, 1, true);
    }

    /**
     * Read the EuroParcel columns of an order from the database, so this works even when overrides are disabled.
     *
     * @param int $idOrder
     *
     * @return array<string, mixed>
     */
    private function getOrderServiceData($idOrder)
    {
        $query = new DbQuery();
        $query->select('europarcel_locker_id, europarcel_carrier_id, europarcel_service_id')
            ->from('orders')
            ->where('id_order = ' . (int) $idOrder);

        $row = Db::getInstance()->getRow($query);

        return $row ?: ['europarcel_locker_id' => null, 'europarcel_carrier_id' => null, 'europarcel_service_id' => null];
    }

    /**
     * Get the locker to use for this checkout: the one picked in this session, else the customer's saved one.
     * The saved locker is checked with EuroParcel before checkout reuses it: a locker EuroParcel no longer
     * serves is forgotten, so the customer picks a new one instead of placing an order that cannot be shipped.
     * Once checked, the locker sits in the session, so the check runs once per checkout.
     *
     * @return Locker|null Locker data, or null when the customer has to pick a locker
     */
    private function getUsableLocker()
    {
        $locker = $this->getSelectedLockerFromSession();
        $idCustomer = (int) $this->context->customer->id;

        if ($locker || !$idCustomer) {
            return $locker;
        }

        $locker = $this->getCustomerLocker($idCustomer);

        if (!$locker) {
            return null;
        }

        if ($this->lockerIsGone($locker['id'])) {
            $this->forgetCustomerLocker($idCustomer);

            return null;
        }

        $this->context->cookie->__set(self::COOKIE_LOCKER, $this->encodeLocker($locker));
        // The checkout is rendered after PrestaShop has written the cookie, so it is written here.
        $this->context->cookie->write();

        return $locker;
    }

    /**
     * Ask EuroParcel whether a locker is gone.
     *
     * @param int $lockerId
     *
     * @return bool True only when EuroParcel says so; when EuroParcel cannot be asked, the locker is kept
     */
    private function lockerIsGone($lockerId)
    {
        $curl = curl_init(self::FIXED_LOCATION_URL . (int) $lockerId);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'EuroParcel-PrestaShop/' . $this->version,
        ]);
        curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // 404: unknown or out-of-service locker; 400: an id no locker can have
        return $status === 404 || $status === 400;
    }

    /**
     * @return Locker|null The locker picked in this checkout
     */
    private function getSelectedLockerFromSession()
    {
        $json = $this->context->cookie->__get(self::COOKIE_LOCKER);

        return $json ? $this->normalizeLocker(json_decode($json, true)) : null;
    }

    private function clearLockerFromSession(): void
    {
        unset($this->context->cookie->{self::COOKIE_LOCKER});
    }

    /**
     * Remember a customer's locker, to suggest it at their next checkout.
     *
     * @param int $idCustomer
     * @param Locker $locker
     *
     * @return bool
     */
    private function saveCustomerLocker($idCustomer, array $locker)
    {
        if (!(int) $idCustomer) {
            return false;
        }

        return Db::getInstance()->update(
            'customer',
            ['europarcel_locker_data' => pSQL($this->encodeLocker($locker), true)],
            'id_customer = ' . (int) $idCustomer,
            1
        );
    }

    /**
     * @param int $idCustomer
     *
     * @return Locker|null The customer's saved locker
     */
    private function getCustomerLocker($idCustomer)
    {
        $query = new DbQuery();
        $query->select('europarcel_locker_data')
            ->from('customer')
            ->where('id_customer = ' . (int) $idCustomer);

        $json = Db::getInstance()->getValue($query);

        return $json ? $this->normalizeLocker(json_decode($json, true)) : null;
    }

    /**
     * @param int $idCustomer
     *
     * @return bool
     */
    private function forgetCustomerLocker($idCustomer)
    {
        return Db::getInstance()->update(
            'customer',
            ['europarcel_locker_data' => null],
            'id_customer = ' . (int) $idCustomer,
            1,
            true
        );
    }

    /**
     * Keep the locker fields the module uses, with their expected types. Lockers come from the browser
     * (the map's message), the cookie and the database, so they are always checked.
     *
     * @param mixed $data
     *
     * @return Locker|null The locker, or null when the data does not describe a locker
     */
    private function normalizeLocker($data)
    {
        if (!is_array($data) || !isset($data['id'], $data['carrier_id'])
            || !Validate::isUnsignedId($data['id']) || !Validate::isUnsignedId($data['carrier_id'])
            || !(int) $data['id'] || !(int) $data['carrier_id']) {
            return null;
        }

        $locker = [
            'id' => (int) $data['id'],
            'carrier_id' => (int) $data['carrier_id'],
        ];

        foreach (['name', 'address', 'carrier_name'] as $field) {
            $locker[$field] = isset($data[$field]) && is_scalar($data[$field])
                ? Tools::substr(trim((string) $data[$field]), 0, 255)
                : '';
        }

        return $locker;
    }

    /**
     * Encode a locker for the cookie and the database. PrestaShop's cookie rejects "|" and "¤":
     * json_encode() already escapes "¤", and "|" is escaped here.
     *
     * @param Locker $locker
     *
     * @return string
     */
    private function encodeLocker(array $locker)
    {
        return str_replace('|', '\\u007c', json_encode($locker));
    }
}
