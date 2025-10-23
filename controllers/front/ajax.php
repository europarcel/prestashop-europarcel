<?php
/**
 * EuroParcel AJAX Controller
 *
 * Handles AJAX requests for locker selection and session management
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

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
                exit(json_encode(['success' => false, 'error' => 'Invalid action']));
        }
    }

    protected function saveLockerSession()
    {
        // Verify token for security
        $token = Tools::getValue('token');
        $expectedToken = Tools::getToken(false);

        if (!$token || !$expectedToken || $token !== $expectedToken) {
            exit(json_encode(['success' => false, 'error' => 'Invalid token']));
        }

        $lockerData = Tools::getValue('locker_data');

        if ($lockerData) {
            // Validate that it is valid JSON
            $decoded = json_decode($lockerData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                exit(json_encode(['success' => false, 'error' => 'Invalid JSON data']));
            }

            // Save in PrestaShop session
            $this->context->cookie->__set('europarcel_locker_data', $lockerData);
            $this->context->cookie->write();

            exit(json_encode(['success' => true]));
        }

        exit(json_encode(['success' => false, 'error' => 'Invalid data']));
    }
}
