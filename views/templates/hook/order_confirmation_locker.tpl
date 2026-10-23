{*
 * EuroParcel Order Confirmation Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="europarcel-order-confirmation">
    <div class="alert alert-info">
        <h4>{l s='Locker information' mod='europarcel'}</h4>
        <p><strong>{l s='Selected locker:' mod='europarcel'}</strong> {$europarcel_locker.name|escape:'html':'UTF-8'}</p>
        <p><strong>{l s='Address:' mod='europarcel'}</strong> {$europarcel_locker.address|escape:'html':'UTF-8'}</p>
        <p><strong>{l s='Courier:' mod='europarcel'}</strong> {$europarcel_locker.carrier_name|escape:'html':'UTF-8'}</p>
        <p class="small">{l s='You will receive a code to pick up the parcel from the locker.' mod='europarcel'}</p>
    </div>
</div>
