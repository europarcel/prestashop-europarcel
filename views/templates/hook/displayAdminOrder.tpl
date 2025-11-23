{*
 * EuroParcel Admin Order Display Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="europarcel-admin-info">
    <h4>{l s='EuroParcel Information' mod='europarcel'}</h4>
    <div class="alert alert-info">
        <p>
            <strong>{l s='Courier:' mod='europarcel'}</strong> {$carrier_name|escape:'html':'UTF-8'} <br>
            <strong>{l s='Service:' mod='europarcel'}</strong> {$europarcel_service|escape:'html':'UTF-8'} <br>
         </p>
        {if $europarcel_locker_id}
            <strong>{l s='Locker id:' mod='europarcel'}</strong> {$europarcel_locker_id|escape:'html':'UTF-8'}
        {/if}
        
    </div>
</div>
