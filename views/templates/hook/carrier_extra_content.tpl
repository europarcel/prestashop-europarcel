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
            <p class="mb-1"><strong>Locker selectat:</strong> {$selected_locker.name}</p>
            <p class="mb-1">{$selected_locker.address}</p>
            <button type="button" class="btn btn-sm btn-primary europarcel-change-locker"
                    data-address="{$checkout_data.address}"
                    data-city="{$checkout_data.city}"
                    data-state="{$checkout_data.state}"
                    data-postcode="{$checkout_data.postcode}"
                    data-couriers="{$courier_ids}">
                Schimbă locker
            </button>
        </div>
    {else}
        <div class="alert alert-info mb-2">
            <p class="mb-2">Pentru a continua, selectează un locker.</p>
            <button type="button" class="btn btn-primary btn-sm europarcel-choose-locker"
                    data-address="{$checkout_data.address}"
                    data-city="{$checkout_data.city}"
                    data-state="{$checkout_data.state}"
                    data-postcode="{$checkout_data.postcode}"
                    data-couriers="{$courier_ids}">
                Alege locker
            </button>
        </div>
    {/if}
</div>
<input type="hidden" name="europarcel_locker_data" id="europarcel_locker_data" value="{$europarcel_locker_data}">