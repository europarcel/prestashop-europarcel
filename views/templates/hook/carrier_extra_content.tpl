{*
 * EuroParcel Carrier Extra Content Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="europarcel-locker-selection">
    {if $selected_locker}
        <div class="alert alert-success mb-2">
            <p class="mb-1"><strong>Locker selectat:</strong> {$selected_locker.name|escape:'html':'UTF-8'}</p>
            <p class="mb-1">{$selected_locker.address|escape:'html':'UTF-8'}</p>
            <button type="button" class="btn btn-sm btn-primary europarcel-change-locker"
                    data-address="{$checkout_data.address|escape:'html':'UTF-8'}"
                    data-city="{$checkout_data.city|escape:'html':'UTF-8'}"
                    data-state="{$checkout_data.state|escape:'html':'UTF-8'}"
                    data-postcode="{$checkout_data.postcode|escape:'html':'UTF-8'}"
                    data-couriers="{$courier_ids|escape:'html':'UTF-8'}">
                Schimbă locker
            </button>
        </div>
    {else}
        <div class="alert alert-info mb-2">
            <p class="mb-2">Pentru a continua, selectează un locker.</p>
            <button type="button" class="btn btn-primary btn-sm europarcel-choose-locker"
                    data-address="{$checkout_data.address|escape:'html':'UTF-8'}"
                    data-city="{$checkout_data.city|escape:'html':'UTF-8'}"
                    data-state="{$checkout_data.state|escape:'html':'UTF-8'}"
                    data-postcode="{$checkout_data.postcode|escape:'html':'UTF-8'}"
                    data-couriers="{$courier_ids|escape:'html':'UTF-8'}">
                Alege locker
            </button>
        </div>
    {/if}
</div>
<input type="hidden" name="europarcel_locker_data" id="europarcel_locker_data" value="{$europarcel_locker_data|escape:'html':'UTF-8'}">