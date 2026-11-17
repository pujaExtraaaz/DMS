// Minimal app bootstrap used when public/build is missing.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const base = document.querySelector('base')?.href || `${window.location.origin}/`;
        const swUrl = new URL('sw.js', base).pathname;
        navigator.serviceWorker.register(swUrl).catch(() => {});
    });
}
