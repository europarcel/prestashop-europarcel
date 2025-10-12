<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EuroParcel extends Module {

    private $carrier_name = 'EuroParcel';
    private $carrier_locker_name = 'EuroParcel Locker';
    private $carrier_delay = 'Livrare în 24-48 de ore';
    private $carrier_locker_delay = 'Livrare a doua zi';
    private $available_lockers = array(
        'easybox' => 'Sameday EasyBox - Delivery to locker',
        'fanbox' => 'Fan Courier FANbox - Delivery to locker',
        'dpdbox' => 'DPD Box - Delivery to locker',
    );
    private $available_carriers = array(
        'cargus_national' => 'Cargus - Delivery to address',
        'dpd_standard' => 'DPD - Delivery to address',
        'fan_courier' => 'Fan Courier - Delivery to address',
        'gls_national' => 'GLS - Delivery to address',
        'sameday' => 'SameDay - Delivery to address',
        'bookurier' => 'Bookurier - Delivery to address',
    );
    private $carrier_settings = [
        'cargus_national' => [
            'carrier' => 'cargus_national',
            'carrier_id' => 1,
            'service_id' => 1
        ],
        'dpd_standard' => [
            'carrier' => 'dpd_standard',
            'carrier_id' => 2,
            'service_id' => 1
        ],
        'fan_courier' => [
            'carrier' => 'fan_courier',
            'carrier_id' => 3,
            'service_id' => 1
        ],
        'gls_national' => [
            'carrier' => 'gls_national',
            'carrier_id' => 4,
            'service_id' => 1
        ],
        'sameday' => [
            'carrier' => 'sameday',
            'carrier_id' => 6,
            'service_id' => 1
        ],
        'bookurier' => [
            'carrier' => 'bookurier',
            'carrier_id' => 5,
            'service_id' => 1
        ],
        'easybox' => [
            'carrier' => 'easybox',
            'carrier_id' => 6,
            'service_id' => 2
        ],
        'fanbox' => [
            'carrier' => 'fanbox',
            'carrier_id' => 3,
            'service_id' => 2
        ],
        'dpdbox' => [
            'carrier' => 'dpdbox',
            'carrier_id' => 2,
            'service_id' => 2
        ],
    ];

    public function __construct() {
        $this->name = 'europarcel';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'EuroParcel';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EuroParcel');
        $this->description = $this->l('Creează și gestionează carrier-ul EuroParcel');
        $this->confirmUninstall = $this->l('Ești sigur că vrei să dezinstalezi?');
    }

    public function install() {
        // Adaugă câmpul europarcel_locker_id în tabela orders
        if (!$this->addLockerIdField()) {
            return false;
        }
        if (!$this->addCarrierIdField()) {
            return false;
        }
        if (!$this->addServiceIdField()) {
            return false;
        }
        return parent::install() &&
                $this->createEuroParcelCarrier() &&
                $this->createEuroParcelCarrierLocker() &&
                //$this->registerHook('displayHeader') &&
                //$this->registerHook('displayCarrierList') &&
                $this->registerHook('displayCarrierExtraContent') &&
                $this->registerHook('actionValidateOrder') &&
                $this->registerHook('displayAdminOrderMain') &&
                $this->registerHook('displayOrderConfirmation') &&
                Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', '') &&
                Configuration::updateValue('EUROPARCEL_DEFAULT_CARRIER', 'cargus_national');
    }

    public function uninstall() {
        // Șterge câmpul europarcel_locker_id (opțional) si europarcel_carrier_id
        // $this->removeLockerIdField();
        //$this->removeCarrierIdField();
        //$this->removeServiceIdField();
        $this->deleteEuroParcelCarrier();
        $this->deleteEuroParcelCarrierLocker();
        return Configuration::deleteByName('EUROPARCEL_CARRIER_ID') &&
                Configuration::deleteByName('EUROPARCEL_LOCKER_CARRIER_ID') &&
                Configuration::deleteByName('EUROPARCEL_LOCKER_TYPES') &&
                Configuration::deleteByName('EUROPARCEL_DEFAULT_CARRIER') &&
                parent::uninstall();
    }

    private function addLockerIdField() {
        // Verifică dacă câmpul există deja
        $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "orders` LIKE 'europarcel_locker_id'";
        $result = Db::getInstance()->executeS($sql);

        if (empty($result)) {
            // Adaugă câmpul europarcel_locker_id în tabela orders
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` 
                    ADD `europarcel_locker_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`";

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Eroare la adăugarea câmpului europarcel_locker_id în tabela orders');
                return false;
            }
        }
        return true;
    }

    private function addCarrierIdField() {
        // Verifică dacă câmpul există deja
        $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "orders` LIKE 'europarcel_carrier_id'";
        $result = Db::getInstance()->executeS($sql);

        if (empty($result)) {
            // Adaugă câmpul europarcel_carrier_id în tabela orders
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` 
                    ADD `europarcel_carrier_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id_carrier`";

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Eroare la adăugarea câmpului europarcel_carrier_id în tabela orders');
                return false;
            }
        }
        return true;
    }

    private function addServiceIdField() {
        // Verifică dacă câmpul există deja
        $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "orders` LIKE 'europarcel_service_id'";
        $result = Db::getInstance()->executeS($sql);

        if (empty($result)) {
            // Adaugă câmpul europarcel_carrier_id în tabela orders
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` 
                    ADD `europarcel_service_id` INT UNSIGNED NULL DEFAULT NULL AFTER `europarcel_carrier_id`";

            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Eroare la adăugarea câmpului europarcel_service_id în tabela orders');
                return false;
            }
        }
        return true;
    }

    private function removeLockerIdField() {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP COLUMN `europarcel_locker_id`";
        return Db::getInstance()->execute($sql);
    }

    private function removeCarrierIdField() {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP COLUMN `europarcel_carrier_id`";
        return Db::getInstance()->execute($sql);
    }

    private function removeServiceIdField() {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP COLUMN `europarcel_service_id`";
        return Db::getInstance()->execute($sql);
    }

    private function createEuroParcelCarrier() {
        $carrier = new Carrier();
        $carrier->name = $this->carrier_name;
        $carrier->active = 1;
        $carrier->deleted = 0;
        $carrier->shipping_handling = 0;
        $carrier->range_behavior = 0;

        // Setează delay-ul pentru toate limbile
        $languages = Language::getLanguages(false);
        $delay = array();
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

        // Adaugă carrier-ul
        if (!$carrier->add()) {
            $this->_errors[] = $this->l('Eroare la crearea carrier-ului EuroParcel');
            return false;
        }

        $carrier_id = (int) $carrier->id;

        // Salvează ID-ul carrier-ului
        Configuration::updateValue('EUROPARCEL_CARRIER_ID', $carrier_id);

        // Adaugă la toate grupurile
        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            Db::getInstance()->insert('carrier_group', array(
                'id_carrier' => $carrier_id,
                'id_group' => (int) $group['id_group']
            ));
        }

        // Creează range-uri de preț
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier_id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '10000';
        if (!$rangePrice->add()) {
            $this->_errors[] = $this->l('Eroare la crearea range-ului de preț');
            return false;
        }

        // Creează range-uri de greutate
        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier_id;
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '10000';
        if (!$rangeWeight->add()) {
            $this->_errors[] = $this->l('Eroare la crearea range-ului de greutate');
            return false;
        }

        // Adaugă la toate zonele
        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert('carrier_zone', array(
                'id_carrier' => $carrier_id,
                'id_zone' => (int) $zone['id_zone']
            ));

            // Adaugă preț de livrare
            Db::getInstance()->insert('delivery', array(
                'id_carrier' => $carrier_id,
                'id_range_price' => (int) $rangePrice->id,
                'id_range_weight' => (int) $rangeWeight->id,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '0.00'
            ));
        }

        return true;
    }

    private function createEuroParcelCarrierLocker() {
        $carrier = new Carrier();
        $carrier->name = $this->carrier_locker_name;
        $carrier->active = 1;
        $carrier->deleted = 0;
        $carrier->shipping_handling = 0;
        $carrier->range_behavior = 0;

        // Setează delay-ul pentru toate limbile
        $languages = Language::getLanguages(false);
        $delay = array();
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

        // Adaugă carrier-ul
        if (!$carrier->add()) {
            $this->_errors[] = $this->l('Eroare la crearea carrier-ului EuroParcel Locker');
            return false;
        }

        $carrier_id = (int) $carrier->id;

        // Salvează ID-ul carrier-ului
        Configuration::updateValue('EUROPARCEL_LOCKER_CARRIER_ID', $carrier_id);

        // Adaugă la toate grupurile
        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            Db::getInstance()->insert('carrier_group', array(
                'id_carrier' => $carrier_id,
                'id_group' => (int) $group['id_group']
            ));
        }

        // Creează range-uri de preț
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier_id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '10000';
        if (!$rangePrice->add()) {
            $this->_errors[] = $this->l('Eroare la crearea range-ului de preț pentru locker');
            return false;
        }

        // Creează range-uri de greutate
        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier_id;
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '10000';
        if (!$rangeWeight->add()) {
            $this->_errors[] = $this->l('Eroare la crearea range-ului de greutate pentru locker');
            return false;
        }

        // Adaugă la toate zonele
        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert('carrier_zone', array(
                'id_carrier' => $carrier_id,
                'id_zone' => (int) $zone['id_zone']
            ));

            // Adaugă preț de livrare
            Db::getInstance()->insert('delivery', array(
                'id_carrier' => $carrier_id,
                'id_range_price' => (int) $rangePrice->id,
                'id_range_weight' => (int) $rangeWeight->id,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '0.00'
            ));
        }

        return true;
    }

    private function deleteEuroParcelCarrier() {
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        if ($carrier_id) {
            $carrier = new Carrier($carrier_id);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = 1;
                return $carrier->update();
            }
        }
        return true;
    }

    private function deleteEuroParcelCarrierLocker() {
        $carrier_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        if ($carrier_id) {
            $carrier = new Carrier($carrier_id);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = 1;
                return $carrier->update();
            }
        }
        return true;
    }

    public function hookDisplayCarrierExtraContent($params) {
        $carrier = $params['carrier'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $carrier['id'] === $locker_carrier_id) {
            $selected_locker = $this->getSelectedLockerFromSession();

            $this->context->smarty->assign(array(
                'selected_locker' => $selected_locker,
                'locker_selection_url' => $this->context->link->getModuleLink($this->name, 'locker')
            ));

            return $this->display(__FILE__, 'carrier_extra_content.tpl');
        }

        return '';
    }

    public function hookActionValidateOrder($params) {
        $order = $params['order'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        $carrier_id = (int) Configuration::get('EUROPARCEL_CARRIER_ID');
        $default_carrier=Configuration::get('EUROPARCEL_DEFAULT_CARRIER');
        if ((int) $order->id_carrier === $locker_carrier_id) {
            $selected_locker = $this->getSelectedLockerFromSession();

            if ($selected_locker && isset($selected_locker['id'])) {
                // Salvează ID-ul locker-ului direct în orders
                $this->saveLockerToOrder($order->id, $selected_locker['id'],$selected_locker['carrier_id']);
                $this->clearLockerFromSession();
            }
        } else if ((int) $order->id_carrier === $carrier_id) {
                $this->saveCarrierToOrder($order->id, $this->carrier_settings[$default_carrier]['carrier_id']);
        }
    }

    public function hookDisplayOrderConfirmation($params) {
        $order = $params['order'];
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $order->id_carrier === $locker_carrier_id) {
            $europarcel_locker_id = $this->getLockerIdFromOrder($order->id);

            if ($europarcel_locker_id) {
                $this->context->smarty->assign(array(
                    'europarcel_locker_id' => $europarcel_locker_id
                ));

                return $this->display(__FILE__, 'order_confirmation_locker.tpl');
            }
        }

        return '';
    }

    public function hookDisplayAdminOrderMain($params) {
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        $locker_carrier_id = (int) Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int) $order->id_carrier === $locker_carrier_id) {
            $europarcel_locker_id = $this->getLockerIdFromOrder($order->id);

            if ($europarcel_locker_id) {
                $this->context->smarty->assign(array(
                    'europarcel_locker_id' => $europarcel_locker_id
                ));

                return $this->display(__FILE__, 'displayAdminOrder.tpl');
            }
        }

        return '';
    }

    private function saveLockerToOrder($order_id, $europarcel_locker_id, $europarcel_carier_id) {
        // Salvează direct în tabela orders
        return Db::getInstance()->update('orders',
                        array('europarcel_locker_id' => pSQL($europarcel_locker_id),'europarcel_service_id'=>2,'europarcel_carrier_id' =>$europarcel_carier_id),
                        'id_order = ' . (int) $order_id
        );
    }
    private function saveCarrierToOrder($order_id,  $europarcel_carier_id) {
        // Salvează direct în tabela orders
        return Db::getInstance()->update('orders',
                        array('europarcel_service_id'=>1,'europarcel_carrier_id' =>$europarcel_carier_id),
                        'id_order = ' . (int) $order_id
        );
    }

    private function getLockerIdFromOrder($order_id) {
        $sql = "SELECT europarcel_locker_id FROM `" . _DB_PREFIX_ . "orders` 
                WHERE id_order = " . (int) $order_id;

        return Db::getInstance()->getValue($sql);
    }

    private function getSelectedLockerFromSession() {
        if (isset($this->context->cookie->europarcel_locker)) {
            return json_decode($this->context->cookie->europarcel_locker, true);
        }
        return null;
    }

    private function clearLockerFromSession() {
        unset($this->context->cookie->europarcel_locker);
    }

    public function getAvailableLockersForSelection() {
        $selected_lockers = explode(',', Configuration::get('EUROPARCEL_LOCKER_TYPES'));
        $available_lockers = array();

        foreach ($this->available_lockers as $key => $label) {
            if (in_array($key, $selected_lockers)) {
                $available_lockers[$key] = $label;
            }
        }

        return $available_lockers;
    }

    public function getContent() {
        if (Tools::isSubmit('save_europarcel_settings')) {
            $locker_types = Tools::getValue('EUROPARCEL_LOCKER_TYPES');
            $default_carrier = Tools::getValue('EUROPARCEL_DEFAULT_CARRIER');
            // Salvează tipurile de lockere selectate
            if (is_array($locker_types)) {
                Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', implode(',', $locker_types));
            } else {
                Configuration::updateValue('EUROPARCEL_LOCKER_TYPES', '');
            }
            if (!empty($default_carrier) && array_key_exists($default_carrier, $this->available_carriers)) {
                Configuration::updateValue('EUROPARCEL_DEFAULT_CARRIER', $default_carrier);
            }
            $output .= $this->displayConfirmation('Setările au fost salvate cu succes.');
        }

        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        $locker_carrier_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        $selected_lockers = explode(',', Configuration::get('EUROPARCEL_LOCKER_TYPES'));
        $default_carrier = Configuration::get('EUROPARCEL_DEFAULT_CARRIER');
        $output .= '
        <div class="panel">
            <div class="panel-heading">Setări EuroParcel</div>
            <div class="panel-body">
                <form method="post">
                <div class="form-group">
                    <label>Transportator default pentru livrare la adresă:</label>
                    <select name="EUROPARCEL_DEFAULT_CARRIER" class="form-control">';
        foreach ($this->available_carriers as $key => $label) {
            $selected = ($key == $default_carrier) ? 'selected' : '';
            $output .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
        }

        $output .= '</select>
                    <p class="help-block">Alege transportatorul care va fi folosit când clienții aleg livrare la adresă</p>
                    </div>
                    <div class="form-group">
                        <label>Tipuri de lockere disponibile pentru clienți:</label>
                        <div class="checkbox">';

        foreach ($this->available_lockers as $key => $label) {
            $checked = in_array($key, $selected_lockers) ? 'checked' : '';
            $output .= '<div class="checkbox">
                                <label>
                                    <input type="checkbox" name="EUROPARCEL_LOCKER_TYPES[]" value="' . $key . '" ' . $checked . '>
                                    ' . $label . '
                                </label>
                            </div>';
        }

        $output .= '</div>
                        <p class="help-block">Selectează tipurile de lockere pe care clienții le pot alege</p>
                    </div>
                    <button type="submit" name="save_europarcel_settings" class="btn btn-primary">Salvează setările</button>
                </form>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-heading">Informații Carrier</div>
            <div class="panel-body">
                <p><strong>EuroParcel Carrier ID:</strong> ' . $carrier_id . '</p>
                <p><strong>EuroParcel Locker Carrier ID:</strong> ' . $locker_carrier_id . '</p>
                <p><strong>Câmp locker_id în tabela orders:</strong> DA</p>
            </div>
        </div>';

        return $output;
    }
}
