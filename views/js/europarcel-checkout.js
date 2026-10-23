/**
 * EuroParcel Checkout integration for PrestaShop
 *
 * Opens the EuroParcel map from the locker picker, saves the locker picked on the map for the
 * checkout and shows it.
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
(function () {
  'use strict';

  const MAP_URL = 'https://maps.europarcel.com';

  // The checkout can re-render the picker, so clicks are handled on the document.
  document.addEventListener('click', (event) => {
    const button = event.target.closest('.europarcel-choose-locker, .europarcel-change-locker');

    if (button) {
      openMap(button);
    }
  });

  window.addEventListener('message', (event) => {
    const data = event.data;

    if (event.origin === MAP_URL && data && data.type === 'locker-selected' && data.locker) {
      selectLocker(data.locker);
    }
  });

  /**
   * @param {HTMLElement} button The choose or change locker button
   */
  function openMap(button) {
    const picker = button.closest('.europarcel-locker-selection');

    if (!picker) {
      return;
    }

    const settings = picker.dataset;
    const params = new URLSearchParams();
    params.append('country_code', 'RO');

    if (settings.city) {
      params.append('locality_name', settings.city);
    }

    if (settings.county) {
      params.append('county_name', settings.county);
    }

    if (settings.couriers) {
      params.append('carrier_id', settings.couriers);
    }

    // The map sends the picked locker back with window.postMessage().
    params.append('callback', 'parent');

    window.EuroparcelModal.show(MAP_URL + '?' + params.toString(), {
      title: settings.mapTitle,
      close: settings.closeLabel,
    });
  }

  /**
   * @param {Object} locker Locker sent by the map
   */
  function selectLocker(locker) {
    window.EuroparcelModal.close();

    saveLocker(locker).then((isSaved) => {
      if (isSaved) {
        showLocker(locker);
      }
    });
  }

  /**
   * Save the locker for the checkout, so the order uses it.
   *
   * @param {Object} locker
   *
   * @return {Promise<boolean>}
   */
  function saveLocker(locker) {
    const body = new FormData();
    body.append('action', 'save_locker_session');
    body.append('locker_data', JSON.stringify(locker));
    body.append('token', window.europarcel_token || '');

    return fetch(window.europarcel_ajax_url, { method: 'POST', body, credentials: 'same-origin' })
      .then((response) => response.json())
      .then((result) => {
        if (!result.success) {
          console.error('EuroParcel: the locker could not be saved:', result.error);
        }

        return Boolean(result.success);
      })
      .catch((error) => {
        console.error('EuroParcel: the locker could not be saved:', error);

        return false;
      });
  }

  /**
   * @param {Object} locker
   */
  function showLocker(locker) {
    document.querySelectorAll('.europarcel-locker-selection').forEach((picker) => {
      picker.querySelector('.europarcel-locker-name').textContent = locker.name || '';
      picker.querySelector('.europarcel-locker-address').textContent = locker.address || '';
      picker.querySelector('.europarcel-locker-selected').hidden = false;
      picker.querySelector('.europarcel-locker-missing').hidden = true;
    });
  }
})();
