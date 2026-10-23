/**
 * EuroParcel Modal functionality
 *
 * Shows the EuroParcel locker map in a modal dialog.
 *
 * @author    EuroParcel
 * @copyright Copyright (c) 2025 EuroParcel
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
(function () {
  'use strict';

  const MODAL_ID = 'europarcel-iframe-modal';
  const MOBILE_MAX_WIDTH = 768;

  // What the modal changes on the page while it is open, restored when it closes.
  let saved = null;

  function closeOnEscape(event) {
    if (event.key === 'Escape' || event.key === 'Esc') {
      window.EuroparcelModal.close();
    }
  }

  window.EuroparcelModal = {
    /**
     * Show the locker map. Does nothing while the map is already open.
     *
     * @param {string} iframeUrl URL of the map
     * @param {{title: string, close: string}} [labels] Translated title of the map and label of its close button
     */
    show(iframeUrl, labels) {
      if (document.getElementById(MODAL_ID)) {
        return;
      }

      const title = (labels && labels.title) || '';
      const closeLabel = (labels && labels.close) || '';
      const isMobile = window.innerWidth <= MOBILE_MAX_WIDTH;
      const body = document.body;
      const html = document.documentElement;

      const modal = document.createElement('div');
      modal.id = MODAL_ID;
      modal.className = isMobile ? 'europarcel-modal europarcel-modal--mobile' : 'europarcel-modal';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.setAttribute('aria-label', title);

      const dialog = document.createElement('div');
      dialog.className = 'europarcel-modal__dialog';

      const closeButton = document.createElement('button');
      closeButton.type = 'button';
      closeButton.className = 'europarcel-modal__close';
      closeButton.setAttribute('aria-label', closeLabel);
      closeButton.textContent = '\u00d7';
      closeButton.addEventListener('click', () => window.EuroparcelModal.close());

      const iframe = document.createElement('iframe');
      iframe.id = 'europarcel-locker-iframe';
      iframe.className = 'europarcel-modal__frame';
      iframe.title = title;
      iframe.src = iframeUrl;

      dialog.append(closeButton, iframe);
      modal.appendChild(dialog);

      // On desktop, a click on the backdrop closes the map.
      if (!isMobile) {
        modal.addEventListener('click', (event) => {
          if (event.target === modal) {
            window.EuroparcelModal.close();
          }
        });
      }

      saved = {
        focus: document.activeElement,
        scrollY: window.pageYOffset,
        htmlOverflow: html.style.overflow,
        bodyStyle: {
          overflow: body.style.overflow,
          height: body.style.height,
          position: body.style.position,
          width: body.style.width,
          top: body.style.top,
        },
      };

      // Stop the page from scrolling behind the map.
      html.style.overflow = 'hidden';
      body.style.overflow = 'hidden';

      if (isMobile) {
        // Mobile browsers need a fixed body; "top" keeps the page where the customer left it.
        body.style.height = '100%';
        body.style.position = 'fixed';
        body.style.width = '100%';
        body.style.top = -saved.scrollY + 'px';
      }

      body.appendChild(modal);
      document.addEventListener('keydown', closeOnEscape);
      closeButton.focus();
    },

    /**
     * Close the locker map and restore the page.
     */
    close() {
      const modal = document.getElementById(MODAL_ID);

      if (!modal) {
        return;
      }

      document.removeEventListener('keydown', closeOnEscape);
      modal.remove();

      if (!saved) {
        return;
      }

      document.documentElement.style.overflow = saved.htmlOverflow;
      Object.assign(document.body.style, saved.bodyStyle);
      window.scrollTo(0, saved.scrollY);

      if (saved.focus && document.body.contains(saved.focus)) {
        saved.focus.focus({ preventScroll: true });
      }

      saved = null;
    },
  };
})();
