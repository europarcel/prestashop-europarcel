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
        $lockers = [
            [
                'id' => 'LOCKER_001',
                'name' => 'Locker Center București',
                'address' => 'Str. Exemplu nr. 1',
                'city' => 'București'
            ],
            [
                'id' => 'LOCKER_002', 
                'name' => 'Locker Mall Afi',
                'address' => 'Bd. Exemplu nr. 2',
                'city' => 'București'
            ]
        ];
        
        $this->context->smarty->assign([
            'lockers' => $lockers,
            'back_url' => $this->context->link->getPageLink('order', true)
        ]);

        $this->setTemplate('module:europarcel/views/templates/front/locker_selection.tpl');
    }

    private function selectLocker()
    {
        $locker_id = Tools::getValue('locker_id');
        $locker_name = Tools::getValue('locker_name');
        $locker_address = Tools::getValue('locker_address');
        $locker_city = Tools::getValue('locker_city');

        $locker_data = [
            'id' => $locker_id,
            'name' => $locker_name,
            'address' => $locker_address,
            'city' => $locker_city
        ];

        $this->context->cookie->europarcel_locker = json_encode($locker_data);
        
        Tools::redirect($this->context->link->getPageLink('order', true));
    }
}