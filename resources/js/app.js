// Livewire (loaded via @livewireScripts) bundles and starts Alpine.js
// itself, so we don't import/start Alpine here to avoid double-init.

// ApexCharts is only needed on the dashboard, so it's dynamically
// imported there instead of bundled into every page's payload.
window.loadApexCharts = () => import('apexcharts').then((m) => m.default);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
