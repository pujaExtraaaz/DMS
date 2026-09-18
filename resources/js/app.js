import './bootstrap';

// Alpine is provided by Livewire (@livewireScripts). Do not import/start
// another copy or the console will warn "Detected multiple instances of Alpine".

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
