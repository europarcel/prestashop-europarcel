<?php

class EuroParcelLockerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        if (Tools::isSubmit('select_locker')) {
            $this->selectLocker();
        } else {
            $this->displayLockerSelection();
        }
    }

    private function displayLockerSelection()
    {
        // Obține lockerele disponibile din setările adminului
        $available_lockers = $this->module->getAvailableLockersForSelection();
        $lockers = array();
        
        // Generează lockere demo pentru fiecare tip selectat
        foreach ($available_lockers as $locker_type => $locker_name) {
            $lockers = array_merge($lockers, $this->generateDemoLockers($locker_type, $locker_name));
        }
        
        $this->context->smarty->assign(array(
            'lockers' => $lockers,
            'back_url' => $this->context->link->getPageLink('order', true)
        ));

        $this->setTemplate('module:europarcel/views/templates/front/locker_selection.tpl');
    }

    private function generateDemoLockers($locker_type, $locker_name)
    {
        // Aici poți înlocui cu un call către API-ul real al fiecărui provider
        $demo_data = array(
            'easybox' => array(
                array('id' => '10001','europarcel_carrier_id'=> 6, 'name' => 'EasyBox București 1', 'address' => 'Bd. Unirii 1', 'city' => 'București'),
                array('id' => '10003','europarcel_carrier_id'=> 6, 'name' => 'EasyBox București 2', 'address' => 'Bd. Magheru 15', 'city' => 'București'),
            ),
            'fanbox' => array(
                array('id' => '10004','europarcel_carrier_id'=> 3, 'name' => 'FANbox Plaza România', 'address' => 'Calea Dorobanți 100', 'city' => 'București'),
                array('id' => '10005','europarcel_carrier_id'=> 3, 'name' => 'FANbox Mall Afi', 'address' => 'Bd. Vasile Milea 4', 'city' => 'București'),
            ),
            'dpdbox' => array(
                array('id' => '10006','europarcel_carrier_id'=> 2, 'name' => 'DPD Box Center', 'address' => 'Str. Mihai Bravu 25', 'city' => 'București'),
                array('id' => '10007','europarcel_carrier_id'=> 2, 'name' => 'DPD Box Nord', 'address' => 'Str. Aviatorilor 40', 'city' => 'București'),
            )
        );
        
        return isset($demo_data[$locker_type]) ? $demo_data[$locker_type] : array();
    }

    private function selectLocker()
    {
        $locker_id = Tools::getValue('locker_id');
        $carrier_id = Tools::getValue('europarcel_carrier_id');
        $locker_name = Tools::getValue('locker_name');
        $locker_address = Tools::getValue('locker_address');
        $locker_city = Tools::getValue('locker_city');

        $locker_data = array(
            'id' => $locker_id,
            'carrier_id' => $carrier_id,
            'name' => $locker_name,
            'address' => $locker_address,
            'city' => $locker_city
        );

        $this->context->cookie->europarcel_locker = json_encode($locker_data);
        
        Tools::redirect($this->context->link->getPageLink('order', true));
    }
}