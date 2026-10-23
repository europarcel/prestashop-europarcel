{*
 * EuroParcel Carrier Extra Content Template
 *
 * Locker picker shown under the locker carrier in the checkout. Both states are rendered; the
 * script shows the selected locker once the customer picks one on the map.
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="europarcel-locker-selection"
     data-city="{$europarcel_city|escape:'html':'UTF-8'}"
     data-county="{$europarcel_county|escape:'html':'UTF-8'}"
     data-couriers="{$europarcel_courier_ids|escape:'html':'UTF-8'}"
     data-map-title="{l s='Choose locker' mod='europarcel'}"
     data-close-label="{l s='Close' mod='europarcel'}">
    <div class="alert alert-success mb-2 europarcel-locker-selected"{if !$europarcel_locker} hidden{/if}>
        <p class="mb-1">
            <strong>{l s='Selected locker:' mod='europarcel'}</strong>
            <span class="europarcel-locker-name">{if $europarcel_locker}{$europarcel_locker.name|escape:'html':'UTF-8'}{/if}</span>
        </p>
        <p class="mb-1 europarcel-locker-address">{if $europarcel_locker}{$europarcel_locker.address|escape:'html':'UTF-8'}{/if}</p>
        <button type="button" class="btn btn-sm btn-primary europarcel-change-locker">
            {l s='Change locker' mod='europarcel'}
        </button>
    </div>
    <div class="alert alert-info mb-2 europarcel-locker-missing"{if $europarcel_locker} hidden{/if}>
        <p class="mb-2">{l s='To continue, select a locker.' mod='europarcel'}</p>
        <button type="button" class="btn btn-primary btn-sm europarcel-choose-locker">
            {l s='Choose locker' mod='europarcel'}
        </button>
    </div>
</div>
