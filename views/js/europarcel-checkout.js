/**
 * EuroParcel Checkout integration for PrestaShop
 */
document.addEventListener('DOMContentLoaded', function () {

    // Butoanele pentru deschiderea modalului
    const chooseLockerBtn = document.querySelector('.europarcel-choose-locker');
    const changeLockerBtn = document.querySelector('.europarcel-change-locker');

    if (chooseLockerBtn || changeLockerBtn) {
        // Adaugă event listeners pentru butoane
        if (chooseLockerBtn) {
            chooseLockerBtn.addEventListener('click', openLockerModal);
        }
        if (changeLockerBtn) {
            changeLockerBtn.addEventListener('click', openLockerModal);
        }
    }

    // Ascultă pentru mesaje de la iframe (pentru selectarea lockerului)
    window.addEventListener('message', handleLockerMessage, false);
});

/**
 * Deschide fereastra modală pentru selecția lockerului
 */
function openLockerModal(event) {
    const button = event.currentTarget;

    // Obține datele din atributurile data-* ale butonului
    const addressData = {
        //address: button.getAttribute('data-address') || '',
        country_code: 'RO',
        locality_name: button.getAttribute('data-city') || '',
        county_name: button.getAttribute('data-state') || ''
                //postcode: button.getAttribute('data-postcode') || ''
    };

    const courierIds = button.getAttribute('data-couriers') || '';

    // Construiește URL-ul pentru iframe cu parametrii necesari
    const iframeUrl = buildLockerIframeUrl(addressData, courierIds);

    // Afișează fereastra modală
    if (typeof EuroparcelModal !== 'undefined') {
        EuroparcelModal.show(iframeUrl);
    } else {
        console.error('EuroparcelModal nu este disponibil');
        // Fallback - deschide în fereastră nouă
        window.open(iframeUrl, '_blank');
    }
}

/**
 * Construiește URL-ul pentru iframe cu parametrii necesari
 */
function buildLockerIframeUrl(addressData, courierIds) {
    const baseUrl = 'https://maps.europarcel.com';
    const params = new URLSearchParams();

    // Adaugă parametrii adresei
    params.append('country_code', 'RO');
    if (addressData.locality_name) {
        params.append('locality_name', addressData.locality_name);
    }
    if (addressData.county_name) {
        params.append('county_name', addressData.county_name);
    }

    // Adaugă ID-urile curierilor
    if (courierIds) {
        params.append('carrier_id', courierIds);
    }

    // Adaugă parametru pentru callback (important!)
    params.append('callback', 'parent');

    return baseUrl + '?' + params.toString();
}

/**
 * Gestionează mesajele primite de la iframe-ul lockerului
 */
function handleLockerMessage(event) {
    // Verifică originea mesajului pentru securitate
    if (event.origin !== 'https://maps.europarcel.com') {
        return;
    }

    const data = event.data;

    // Verifică dacă este un mesaj de selecție locker
    if (data.type === 'locker-selected') {
        handleLockerSelection(data.locker);
    }
}

/**
 * Gestionează selecția unui locker
 */
function handleLockerSelection(lockerData) {
    // Salvează datele lockerului în câmpurile hidden
    const lockerDataField = document.getElementById('europarcel_locker_data');
    if (lockerDataField) {
        lockerDataField.value = JSON.stringify(lockerData);
    }
    // Salvează IMEDIAT în cookie
    saveLockerToSession(JSON.stringify(lockerData));
    // Actualizează afișajul în timp real
    updateLockerDisplay(lockerData);

    // Închide fereastra modală
    if (typeof EuroparcelModal !== 'undefined') {
        EuroparcelModal.close();
    }

    // Opțional: Reîncarcă transporturile dacă este necesar
    setTimeout(() => {
        if (typeof updateCarrierSelection !== 'undefined') {
            updateCarrierSelection();
        }
    }, 500);
}
/**
 * Salvează lockerul în cookie
 */
function saveLockerToSession(lockerDataJson) {
    const formData = new FormData();
    formData.append('action', 'save_locker_session');
    formData.append('locker_data', lockerDataJson);
    formData.append('token', window.europarcel_token || '');

    fetch(window.europarcel_ajax_url, {
        method: 'POST',
        body: formData
    })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Locker saved successfully to PrestaShop session');
                } else {
                    console.error('Error saving locker to PrestaShop session:', data.error);
                }
            })
            .catch(error => {
                console.error('Error saving locker to PrestaShop session:', error);
            });
}
/**
 * Actualizează afișajul lockerului selectat
 */
function updateLockerDisplay(lockerData) {
    const lockerContainer = document.querySelector('.europarcel-locker-selection');

    if (!lockerContainer)
        return;

    // Obține datele curente de pe buton (pentru a le păstra)
    const currentButton = lockerContainer.querySelector('.europarcel-change-locker, .europarcel-choose-locker');
    const address = currentButton ? currentButton.getAttribute('data-address') : '';
    const city = currentButton ? currentButton.getAttribute('data-city') : '';
    const state = currentButton ? currentButton.getAttribute('data-state') : '';
    const postcode = currentButton ? currentButton.getAttribute('data-postcode') : '';
    const couriers = currentButton ? currentButton.getAttribute('data-couriers') : '';

    const displayHtml = `
        <div class="alert alert-success mb-2">
            <p class="mb-1"><strong>Locker selectat:</strong> ${lockerData.name}</p>
            <p class="mb-1">${lockerData.address}</p>
            <button type="button" class="btn btn-sm btn-primary europarcel-change-locker"
                    data-address="${address}"
                    data-city="${city}"
                    data-state="${state}"
                    data-postcode="${postcode}"
                    data-couriers="${couriers}">
                Schimbă locker
            </button>
        </div>
    `;

    lockerContainer.innerHTML = displayHtml;

    // Reatașează event listener pentru butonul de schimbare
    const changeBtn = lockerContainer.querySelector('.europarcel-change-locker');
    if (changeBtn) {
        changeBtn.addEventListener('click', openLockerModal);
    }
}
