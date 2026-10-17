/**
 * Backward-compatibility wrapper: name-uppercase.js now delegates to DMS Global Title Case Formatting.
 */
(function () {
    'use strict';
    // If title-case.js already loaded or will load, this ensures consistent behavior
    if (!window.dmsToTitleCase) {
        const s = document.createElement('script');
        s.src = '/js/title-case.js';
        s.defer = true;
        document.head.appendChild(s);
    }
})();
