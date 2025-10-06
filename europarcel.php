<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EuroParcel extends Module {

    private $carrier_name = 'EuroParcel';
    private $carrier_locker_name = 'EuroParcel Locker';
    private $carrier_delay = 'Livrare în 24-48 de ore';
    private $carrier_locker_delay = 'Livrare a doua zi';

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
        if (!parent::install()) {
            $this->_errors[] = 'Eroare la instalarea părinte';
            return false;
        }

        // Creează primul carrier
        if (!$this->createEuroParcelCarrier()) {
            $this->_errors[] = 'Eroare la crearea carrier-ului EuroParcel';
            return false;
        }

        // Creează carrier-ul locker
        if (!$this->createEuroParcelCarrierLocker()) {
            $this->_errors[] = 'Eroare la crearea carrier-ului Locker';
            return false;
        }

        // Verifică dacă ID-urile sunt salvate
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        $locker_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ($carrier_id == 0 || $locker_id == 0) {
            $this->_errors[] = 'ID-urile carrier-elor nu au fost salvate corect';
            return false;
        }

        return $this->registerHook('displayHeader') &&
                $this->registerHook('displayCarrierList') &&
                $this->registerHook('displayCarrierExtraContent') &&
                $this->registerHook('actionValidateOrder');
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

    public function hookDisplayCarrierList($params) {
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        $locker_carrier_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        $this->context->smarty->assign(array(
            'europarcel_carrier_id' => $carrier_id,
            'europarcel_locker_carrier_id' => $locker_carrier_id,
            'module_dir' => $this->_path
        ));

        return $this->display(__FILE__, 'displayCarrierList.tpl');
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

        if ((int) $order->id_carrier === $locker_carrier_id) {
            $selected_locker = $this->getSelectedLockerFromSession();

            if ($selected_locker && isset($selected_locker['id'])) {
                $this->saveLockerToOrderCarrier($order->id, $selected_locker['id']);
                $this->clearLockerFromSession();
            }
        }
    }

    private function saveLockerToOrderCarrier($order_id, $locker_id) {
        return Db::getInstance()->update('order_carrier',
                        array('tracking_number' => pSQL($locker_id)),
                        'id_order = ' . (int) $order_id
        );
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

    public function getContent() {
        return $this->displayConfirmation('Modulul EuroParcel este activ.') .
                $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }
}
