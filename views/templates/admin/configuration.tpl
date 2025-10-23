{*
 * EuroParcel Carrier Extra Content Template
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> EuroParcel Settings
    </div>
    <div class="panel-body">
        <form method="post" action="{$post_url|escape:'html':'UTF-8'}">
            <div class="form-group">
                <label for="EUROPARCEL_DEFAULT_CARRIER">Home Delivery Carrier:</label>
                <select name="EUROPARCEL_DEFAULT_CARRIER" id="EUROPARCEL_DEFAULT_CARRIER" class="form-control fixed-width-xxl">
                    <option value="">-- Select Carrier --</option>
                    {foreach from=$carriers item=carrier}
                        <option value="{$carrier.key|escape:'html':'UTF-8'}" {if $carrier.key == $default_carrier}selected{/if}>
                            {$carrier.name|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
                <p class="help-block">Choose the carrier to be used when customers select home delivery</p>
            </div>
            
            <div class="form-group">
                <label>Available locker types for customers:</label>
                <div class="checkbox">
                    {foreach from=$available_lockers item=locker}
                        <div class="checkbox" style="margin-bottom: 10px;">
                            <label style="display: flex; align-items: center;">
                                <input type="checkbox" name="EUROPARCEL_LOCKER_TYPES[]" value="{$locker.key|escape:'html':'UTF-8'}" 
                                       {if in_array($locker.key, $selected_lockers)}checked{/if} style="margin-right: 8px;">
                                {if $carrier_logos[$locker.key]}
                                    <img src="{$module_path|escape:'html':'UTF-8'}views/img/{$carrier_logos[$locker.key]|escape:'html':'UTF-8'}" 
                                         alt="{$locker.key|escape:'html':'UTF-8'}" 
                                         style="height: 20px; width: auto; object-fit: contain; vertical-align: middle; margin-right: 10px; max-width: 60px;">
                                {/if}
                                <span>{$locker.name|escape:'html':'UTF-8'}</span>
                            </label>
                        </div>
                    {/foreach}
                </div>
                <p class="help-block">Select the locker types that customers can choose</p>
            </div>
            
            <button type="submit" name="save_europarcel_settings" class="btn btn-primary">
                <i class="icon-save"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-info"></i> Carriers Information
    </div>
    <div class="panel-body">
        <p><strong>EuroParcel Carrier ID:</strong> {$carrier_id|escape:'html':'UTF-8'}</p>
        <p><strong>EuroParcel Locker Carrier ID:</strong> {$locker_carrier_id|escape:'html':'UTF-8'}</p>
    </div>
</div>