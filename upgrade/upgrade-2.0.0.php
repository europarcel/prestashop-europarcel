<?php

/**
 * EuroParcel - PrestaShop Shipping Module
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Put the new eAWB logo on the EuroParcel carriers. The module copies its logo to its carriers only when it is
 * installed, so a shop that installed an earlier version still shows the old logo at checkout.
 * A carrier logo is replaced only while it is still the old EuroParcel logo, so a logo the merchant uploaded stays.
 * A logo that cannot be replaced never fails the upgrade.
 *
 * @param EuroParcel $module
 *
 * @return bool
 */
function upgrade_module_2_0_0($module)
{
    // md5 of the logo.png that versions 1.0.1 to 1.0.4 copied to their carriers
    $old_logo_md5 = '35ceb681a09ea6b63c2a5bb56c314f4a';
    $new_logo = dirname(__FILE__) . '/../logo.png';

    if (!file_exists($new_logo)) {
        return true;
    }

    $sql = new DbQuery();
    $sql->select('id_carrier')
            ->from('carrier')
            ->where('external_module_name = \'' . pSQL($module->name) . '\'')
            ->where('deleted = 0');

    $carriers = Db::getInstance()->executeS($sql);
    if (!$carriers) {
        return true;
    }

    foreach ($carriers as $carrier) {
        $id_carrier = (int) $carrier['id_carrier'];
        $logo = _PS_SHIP_IMG_DIR_ . $id_carrier . '.jpg';

        if (!file_exists($logo) || md5_file($logo) !== $old_logo_md5 || !@copy($new_logo, $logo)) {
            continue;
        }

        @chmod($logo, 0644);

        // The admin carrier list caches a thumbnail of each carrier logo; drop it so the list shows the new logo
        foreach (['', '_*'] as $shop_suffix) {
            $thumbnails = glob(_PS_TMP_IMG_DIR_ . 'carrier_mini_' . $id_carrier . $shop_suffix . '.jpg');
            foreach ($thumbnails ?: [] as $thumbnail) {
                @unlink($thumbnail);
            }
        }
    }

    return true;
}
