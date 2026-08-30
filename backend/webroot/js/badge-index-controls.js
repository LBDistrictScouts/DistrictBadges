(function () {
    'use strict';

    var controls = document.querySelector('[data-badge-index-controls]');
    if (!controls) {
        return;
    }

    var storageKey = 'badge-index-controls-open';
    try {
        var cachedState = window.sessionStorage.getItem(storageKey);
        if (cachedState !== null) {
            controls.open = cachedState === 'true';
        }
        controls.addEventListener('toggle', function () {
            window.sessionStorage.setItem(storageKey, String(controls.open));
        });
    } catch (error) {
        // The controls remain usable when browser storage is unavailable.
    }
}());
