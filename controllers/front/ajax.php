<?php

class EuroparcelAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'save_locker_session':
                $this->saveLockerSession();
                break;
            default:
                die(json_encode(['success' => false, 'error' => 'Invalid action']));
        }
    }
    
    protected function saveLockerSession()
    {
        // Verifică token-ul pentru securitate
        if (!Tools::getValue('token') || !Tools::getToken(false)) {
            die(json_encode(['success' => false, 'error' => 'Invalid token']));
        }
        
        $lockerData = Tools::getValue('locker_data');
        
        if ($lockerData) {
            // Salvează în sesiunea PrestaShop
            $this->context->cookie->__set('europarcel_locker_data', $lockerData);
            $this->context->cookie->write();
            
            die(json_encode(['success' => true]));
        }
        
        die(json_encode(['success' => false, 'error' => 'Invalid data']));
    }
}

