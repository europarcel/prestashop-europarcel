<div class="container">
    <h1>Selectează Locker EuroParcel</h1>
    
    <div class="lockers-list">
        {foreach from=$lockers item=locker}
            <div class="card mb-3">
                <div class="card-body">
                    <h4>{$locker.name}</h4>
                    <p>{$locker.address}, {$locker.city}</p>
                    <form method="post">
                        <input type="hidden" name="locker_id" value="{$locker.id}">
                        <input type="hidden" name="locker_name" value="{$locker.name}">
                        <input type="hidden" name="locker_address" value="{$locker.address}">
                        <input type="hidden" name="locker_city" value="{$locker.city}">
                        <button type="submit" name="select_locker" class="btn btn-primary">
                            Selectează
                        </button>
                    </form>
                </div>
            </div>
        {/foreach}
    </div>
    
    <a href="{$back_url}" class="btn btn-secondary">Înapoi la checkout</a>
</div>