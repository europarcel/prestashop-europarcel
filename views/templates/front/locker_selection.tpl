<div class="container">
    <h1>{l s='Selectează Locker EuroParcel' mod='europarcel'}</h1>
    
    {if $lockers}
        <div class="lockers-list">
            {foreach from=$lockers item=locker}
                <div class="card mb-3">
                    <div class="card-body">
                        <h4>{$locker.name}</h4>
                        <p class="text-muted">{$locker.address}, {$locker.city}</p>
                        <form method="post">
                            <input type="hidden" name="locker_id" value="{$locker.id}">
                            <input type="hidden" name="locker_name" value="{$locker.name}">
                            <input type="hidden" name="locker_address" value="{$locker.address}">
                            <input type="hidden" name="locker_city" value="{$locker.city}">
                            <input type="hidden" name="europarcel_carrier_id" value="{$locker.europarcel_carrier_id}">
                            <button type="submit" name="select_locker" class="btn btn-primary">
                                {l s='Selectează acest locker' mod='europarcel'}
                            </button>
                        </form>
                    </div>
                </div>
            {/foreach}
        </div>
    {else}
        <div class="alert alert-warning">
            {l s='Nu există lockere disponibile momentan. Vă rugăm să alegeți o altă metodă de livrare.' mod='europarcel'}
        </div>
    {/if}
    
    <a href="{$back_url}" class="btn btn-secondary">{l s='Înapoi la checkout' mod='europarcel'}</a>
</div>

<style>
.lockers-list {
    max-height: 600px;
    overflow-y: auto;
}
.card {
    border-left: 4px solid #007bff;
}
</style>