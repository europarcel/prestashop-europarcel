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
        // Adaugă câmpul locker_id în tabela orders
        if (!$this->addLockerIdField()) {
            return false;
        }

        return parent::install() &&
               $this->createEuroParcelCarrier() &&
               $this->createEuroParcelCarrierLocker() &&
               $this->registerHook('displayHeader') &&
               $this->registerHook('displayCarrierList') &&
               $this->registerHook('displayCarrierExtraContent') &&
               $this->registerHook('actionValidateOrder') &&
               $this->registerHook('displayAdminOrderMain') &&
               $this->registerHook('displayOrderConfirmation'); //&&
               //Configuration::updateValue('EUROPARCEL_CARRIER_ID', 0) &&
               //Configuration::updateValue('EUROPARCEL_LOCKER_CARRIER_ID', 0);
    }

    public function uninstall() {
        // Șterge câmpul locker_id (opțional)
        // $this->removeLockerIdField();
        
        $this->deleteEuroParcelCarrier();
        $this->deleteEuroParcelCarrierLocker();
        return Configuration::deleteByName('EUROPARCEL_CARRIER_ID') &&
               Configuration::deleteByName('EUROPARCEL_LOCKER_CARRIER_ID') &&
               parent::uninstall();
    }

    private function addLockerIdField() {
        // Verifică dacă câmpul există deja
        $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "orders` LIKE 'locker_id'";
        $result = Db::getInstance()->executeS($sql);
        
        if (empty($result)) {
            // Adaugă câmpul locker_id în tabela orders
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` 
                    ADD `locker_id` VARCHAR(50) NULL AFTER `id_order`";
            
            if (!Db::getInstance()->execute($sql)) {
                $this->_errors[] = $this->l('Eroare la adăugarea câmpului locker_id în tabela orders');
                return false;
            }
        }
        return true;
    }

    private function removeLockerIdField() {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP COLUMN `locker_id`";
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
        $locker_carrier_id = (int)Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int)$carrier['id'] === $locker_carrier_id) {
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
        $locker_carrier_id = (int)Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int)$order->id_carrier === $locker_carrier_id) {
            $selected_locker = $this->getSelectedLockerFromSession();

            if ($selected_locker && isset($selected_locker['id'])) {
                // Salvează ID-ul locker-ului direct în orders
                $this->saveLockerToOrder($order->id, $selected_locker['id']);
                $this->clearLockerFromSession();
            }
        }
    }

    public function hookDisplayOrderConfirmation($params) {
        $order = $params['order'];
        $locker_carrier_id = (int)Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int)$order->id_carrier === $locker_carrier_id) {
            $locker_id = $this->getLockerIdFromOrder($order->id);

            if ($locker_id) {
                $this->context->smarty->assign(array(
                    'locker_id' => $locker_id
                ));

                return $this->display(__FILE__, 'order_confirmation_locker.tpl');
            }
        }
        
        return '';
    }

    public function hookDisplayAdminOrderMain($params) {
        $id_order = (int)$params['id_order'];
        $order = new Order($id_order);
        $locker_carrier_id = (int)Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');

        if ((int)$order->id_carrier === $locker_carrier_id) {
            $locker_id = $this->getLockerIdFromOrder($order->id);

            if ($locker_id) {
                $this->context->smarty->assign(array(
                    'locker_id' => $locker_id
                ));

                return $this->display(__FILE__, 'displayAdminOrder.tpl');
            }
        }
        
        return '';
    }

    private function saveLockerToOrder($order_id, $locker_id) {
        // Salvează direct în tabela orders
        return Db::getInstance()->update('orders', 
            array('locker_id' => pSQL($locker_id)), 
            'id_order = ' . (int)$order_id
        );
    }

    private function getLockerIdFromOrder($order_id) {
        $sql = "SELECT locker_id FROM `" . _DB_PREFIX_ . "orders` 
                WHERE id_order = " . (int)$order_id;

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

    public function getContent() {
        $carrier_id = Configuration::get('EUROPARCEL_CARRIER_ID');
        $locker_id = Configuration::get('EUROPARCEL_LOCKER_CARRIER_ID');
        
        $output = $this->displayConfirmation('Modulul EuroParcel este activ.');
        $output .= '<div class="panel">';
        $output .= '<div class="panel-heading">Informații Carrier</div>';
        $output .= '<div class="panel-body">';
        $output .= '<p><strong>EuroParcel Carrier ID:</strong> ' . $carrier_id . '</p>';
        $output .= '<p><strong>EuroParcel Locker Carrier ID:</strong> ' . $locker_id . '</p>';
        $output .= '<p><strong>Câmp locker_id adăugat în tabela orders:</strong> DA</p>';
        $output .= '</div></div>';
        
        return $output;
    }
}