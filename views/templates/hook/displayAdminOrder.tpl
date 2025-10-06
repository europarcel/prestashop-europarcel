<div class="europarcel-admin-info">
    <h4>{l s='Informații EuroParcel' mod='europarcel'}</h4>
    <div class="alert alert-info">
        <p>
            <strong>{l s='Curier:' mod='europarcel'}</strong> {$order_carrier->name}<br>
            <strong>{l s='Referință comandă:' mod='europarcel'}</strong> {$order_reference}
        </p>
        {if $order_carrier->url}
            <a href="{$order_carrier->url}" target="_blank" class="btn btn-primary">
                <i class="material-icons">open_in_new</i>
                {l s='Urmărește livrarea' mod='europarcel'}
            </a>
        {/if}
    </div>
</div>
