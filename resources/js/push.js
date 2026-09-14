// Web Push subscription helper, exposed as window.MonarchPush so the
// "Notifiche" settings tab (plain Alpine, no build step of its own) can
// drive it without importing anything.

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i++) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function isRunningInstalled() {
    // Standalone display-mode covers Android/desktop PWA installs; iOS
    // Safari never sets that media query and uses its own legacy flag
    // instead (only true once added to the Home Screen).
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function isSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

async function getSubscription() {
    if (!isSupported()) return null;
    const registration = await navigator.serviceWorker.ready;
    return registration.pushManager.getSubscription();
}

async function subscribe() {
    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey) throw new Error('Chiave VAPID non configurata.');

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        return { status: permission };
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });

    await fetch('/push-subscriptions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
        },
        body: JSON.stringify(subscription.toJSON()),
    });

    return { status: 'granted' };
}

async function unsubscribe() {
    const subscription = await getSubscription();
    if (!subscription) return;

    const endpoint = subscription.endpoint;
    await subscription.unsubscribe();

    await fetch('/push-subscriptions', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
        },
        body: JSON.stringify({ endpoint }),
    });
}

window.MonarchPush = {
    isRunningInstalled,
    isSupported,
    getSubscription,
    subscribe,
    unsubscribe,
    permission: () => (isSupported() ? Notification.permission : 'unsupported'),
};
