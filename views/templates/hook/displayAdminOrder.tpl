<div class="europarcel-admin-info">
    <h4>{l s='EuroParcel Information' mod='europarcel'}</h4>
    <div class="alert alert-info">
        <p>
            <strong>{l s='Courier:' mod='europarcel'}</strong> {$order_carrier->name}<br>
            <strong>{l s='Order reference:' mod='europarcel'}</strong> {$order_reference}
        </p>
        {if $order_carrier->url}
            <a href="{$order_carrier->url}" target="_blank" class="btn btn-primary">
                <i class="material-icons">open_in_new</i>
                {l s='Track delivery' mod='europarcel'}
            </a>
        {/if}
    </div>
</div>
