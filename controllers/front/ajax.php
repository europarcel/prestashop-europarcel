<?php

/**
 * EuroParcel AJAX Controller
 *
 * Stores the locker the customer picked on the EuroParcel map for the current checkout.
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
    /** @var EuroParcel */
    public $module;

    /**
     * Answer with JSON instead of rendering a page.
     */
    public function initContent(): void
    {
        if (Tools::getValue('action') !== 'save_locker_session') {
            $this->respond(['success' => false, 'error' => 'Invalid action'], 400);
        }

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(['success' => false, 'error' => 'POST required'], 405);
        }

        $token = (string) Tools::getValue('token');

        if ($token === '' || !hash_equals(Tools::getToken(false), $token)) {
            $this->respond(['success' => false, 'error' => 'Invalid token'], 403);
        }

        if (!$this->module->saveSelectedLocker(json_decode((string) Tools::getValue('locker_data'), true))) {
            $this->respond(['success' => false, 'error' => 'Invalid locker data'], 400);
        }

        $this->respond(['success' => true]);
    }

    /**
     * @param array<string, mixed> $response
     * @param int $httpCode
     *
     * @return never
     */
    private function respond(array $response, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        exit(json_encode($response));
    }
}
