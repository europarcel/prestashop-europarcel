/**
 * EuroParcel Checkout integration for PrestaShop
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
document.addEventListener('DOMContentLoaded', function () {
	// Buttons for opening the modal
	const chooseLockerBtn = document.querySelector('.europarcel-choose-locker');
	const changeLockerBtn = document.querySelector('.europarcel-change-locker');

	if (chooseLockerBtn || changeLockerBtn) {
		// Add event listeners for buttons
		if (chooseLockerBtn) {
			chooseLockerBtn.addEventListener('click', openLockerModal);
		}
		if (changeLockerBtn) {
			changeLockerBtn.addEventListener('click', openLockerModal);
		}
	}

	// Listen for messages from iframe (for locker selection)
	window.addEventListener('message', handleLockerMessage, false);
});

/**
 * Opens the modal window for locker selection
 */
function openLockerModal(event) {
	const button = event.currentTarget;

	// Get data from data-* attributes of the button
	const addressData = {
		//address: button.getAttribute('data-address') || '',
		country_code: 'RO',
		locality_name: button.getAttribute('data-city') || '',
		county_name: button.getAttribute('data-state') || '',
		//postcode: button.getAttribute('data-postcode') || ''
	};

	const courierIds = button.getAttribute('data-couriers') || '';

	// Build the iframe URL with necessary parameters
	const iframeUrl = buildLockerIframeUrl(addressData, courierIds);

	// Display the modal window
	if (typeof EuroparcelModal !== 'undefined') {
		EuroparcelModal.show(iframeUrl);
	} else {
		console.error('EuroparcelModal is not available');
		// Fallback - open in new window
		window.open(iframeUrl, '_blank');
	}
}

/**
 * Builds the iframe URL with necessary parameters
 */
function buildLockerIframeUrl(addressData, courierIds) {
	const baseUrl = 'https://maps.europarcel.com';
	const params = new URLSearchParams();

	// Add address parameters
	params.append('country_code', 'RO');
	if (addressData.locality_name) {
		params.append('locality_name', addressData.locality_name);
	}
	if (addressData.county_name) {
		params.append('county_name', addressData.county_name);
	}

	// Add courier IDs
	if (courierIds) {
		params.append('carrier_id', courierIds);
	}

	// Add callback parameter (important!)
	params.append('callback', 'parent');

	return baseUrl + '?' + params.toString();
}

/**
 * Handles messages received from the locker iframe
 */
function handleLockerMessage(event) {
	// Verify message origin for security
	if (event.origin !== 'https://maps.europarcel.com') {
		return;
	}

	const data = event.data;

	// Check if it's a locker selection message
	if (data.type === 'locker-selected') {
		handleLockerSelection(data.locker);
	}
}

/**
 * Handles locker selection
 */
function handleLockerSelection(lockerData) {
	// Save locker data in hidden fields
	const lockerDataField = document.getElementById('europarcel_locker_data');
	if (lockerDataField) {
		lockerDataField.value = JSON.stringify(lockerData);
	}
	// Save IMMEDIATELY to cookie
	saveLockerToSession(JSON.stringify(lockerData));
	// Update the display in real time
	updateLockerDisplay(lockerData);

	// Close the modal window
	if (typeof EuroparcelModal !== 'undefined') {
		EuroparcelModal.close();
	}

	// Optional: Reload carriers if necessary
	setTimeout(() => {
		if (typeof updateCarrierSelection !== 'undefined') {
			updateCarrierSelection();
		}
	}, 500);
}
/**
 * Saves the locker to cookie
 */
function saveLockerToSession(lockerDataJson) {
	const formData = new FormData();
	formData.append('action', 'save_locker_session');
	formData.append('locker_data', lockerDataJson);
	formData.append('token', window.europarcel_token || '');

	fetch(window.europarcel_ajax_url, {
		method: 'POST',
		body: formData,
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
 * Updates the selected locker display
 */
function updateLockerDisplay(lockerData) {
	const lockerContainer = document.querySelector('.europarcel-locker-selection');

	if (!lockerContainer) return;

	// Get current data from button (to preserve it)
	const currentButton = lockerContainer.querySelector(
		'.europarcel-change-locker, .europarcel-choose-locker'
	);
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
                Schimbă locker-ul
            </button>
        </div>
    `;

	lockerContainer.innerHTML = displayHtml;

	// Reattach event listener for the change button
	const changeBtn = lockerContainer.querySelector('.europarcel-change-locker');
	if (changeBtn) {
		changeBtn.addEventListener('click', openLockerModal);
	}
}
