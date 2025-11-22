{*
 * EuroParcel Order Confirmation Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="europarcel-order-confirmation">
    <div class="alert alert-info">
        <h4>{l s='Informații locker' mod='europarcel'}</h4>
        <p><strong>{l s='Locker selectat:' mod='europarcel'}</strong> {$locker_info.name}</p>
        <p><strong>{l s='Adresă:' mod='europarcel'}</strong> {$locker_info.address}</p>
        <p><strong>{l s='Curier:' mod='europarcel'}</strong> {$locker_info.carrier_name}</p>
        <p class="small">{l s='Veți primi un cod pentru ridicarea pachetului din locker.' mod='europarcel'}</p>
    </div>
</div>
