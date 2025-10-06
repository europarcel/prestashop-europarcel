<div class="europarcel-locker-selection">
    {if $selected_locker}
        <div class="alert alert-success mb-2">
            <p class="mb-1"><strong>Locker selectat:</strong> {$selected_locker.name}</p>
            <p class="mb-1">{$selected_locker.address}, {$selected_locker.city}</p>
            <a href="{$locker_selection_url}" target="_blank" class="btn btn-sm btn-primary">
                Schimbă locker
            </a>
        </div>
    {else}
        <div class="alert alert-info mb-2">
            <p class="mb-2">Pentru a continua, selectează un locker EuroParcel.</p>
            <a href="{$locker_selection_url}" target="_blank" class="btn btn-primary btn-sm">
                Alege locker
            </a>
        </div>
    {/if}
</div>