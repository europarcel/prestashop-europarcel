{*
 * EuroParcel Configuration Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="panel europarcel-settings">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='EuroParcel Settings' mod='europarcel'}
    </div>
    <div class="panel-body">
        <form method="post" action="{$europarcel_form_action|escape:'html':'UTF-8'}">
            <div class="form-group">
                <label for="EUROPARCEL_DEFAULT_CARRIER">{l s='Home Delivery Carrier:' mod='europarcel'}</label>
                <select name="EUROPARCEL_DEFAULT_CARRIER" id="EUROPARCEL_DEFAULT_CARRIER" class="form-control fixed-width-xxl">
                    <option value="">{l s='-- Select Carrier --' mod='europarcel'}</option>
                    {foreach $europarcel_home_services as $service}
                        <option value="{$service.key|escape:'html':'UTF-8'}"{if $service.selected} selected{/if}>
                            {$service.label|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='Choose the carrier to be used when customers select home delivery' mod='europarcel'}</p>
            </div>

            <div class="form-group">
                <label>{l s='Available locker types for customers:' mod='europarcel'}</label>
                {foreach $europarcel_locker_services as $service}
                    <div class="checkbox europarcel-locker-type">
                        <label>
                            <input type="checkbox" name="EUROPARCEL_LOCKER_TYPES[]" value="{$service.key|escape:'html':'UTF-8'}"{if $service.checked} checked{/if}>
                            <img src="{$service.logo|escape:'html':'UTF-8'}" alt="{$service.key|escape:'html':'UTF-8'}">
                            <span>{$service.label|escape:'html':'UTF-8'}</span>
                        </label>
                    </div>
                {/foreach}
                <p class="help-block">{l s='Select the locker types that customers can choose' mod='europarcel'}</p>
            </div>

            <button type="submit" name="save_europarcel_settings" class="btn btn-primary">
                <i class="icon-save"></i> {l s='Save Settings' mod='europarcel'}
            </button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-info"></i> {l s='Carriers Information' mod='europarcel'}
    </div>
    <div class="panel-body">
        <p><strong>{l s='EuroParcel Carrier ID:' mod='europarcel'}</strong> {$europarcel_home_carrier_id|intval}</p>
        <p><strong>{l s='EuroParcel Locker Carrier ID:' mod='europarcel'}</strong> {$europarcel_locker_carrier_id|intval}</p>
    </div>
</div>
