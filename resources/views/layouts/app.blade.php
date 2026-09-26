<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#1A6B72">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">
  <title>{{ $title ?? 'The Muhsinat Club' }}</title>
  <link rel="icon" href="{{ asset('images/img1.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/img1.png') }}">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @endif
  @livewireStyles
</head>
<body>

<div class="app-shell">

  {{-- Top bar --}}
  <header class="top-bar">
    <div style="display:inline-flex;align-items:center;gap:10px;">
      @stack('backButton')
      <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;">
        <img src="{{ asset('images/img1.png') }}"
             alt="TMC"
             style="width:32px;height:32px;object-fit:contain;">
      </a>
    </div>
    <span class="text-[11px] font-medium text-teal-dk/70" style="font-feature-settings:'tnum';">
      {{ now()->hijri('j M Y') }}
    </span>
    @if(auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'moderator', 'content_editor']))
      <a href="/admin" class="text-[10px] font-semibold uppercase tracking-wider text-gold no-underline hover:text-teal transition-colors">Admin</a>
    @endif
    <livewire:notifications.bell />
  </header>

  {{-- Main content --}}
  <main>
    {{ $slot }}
  </main>

  {{-- Bottom nav --}}
  <livewire:layout.bottom-nav />

</div>

{{-- Toast: success --}}
@if(session('success'))
  <div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 3000)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="toast toast-success">
    ✓ {{ session('success') }}
  </div>
@endif

{{-- Toast: error --}}
@if(session('error'))
  <div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="toast toast-error">
    {{ session('error') }}
  </div>
@endif

@livewireScripts

<style>
#install-banner:not(.hidden),
#ios-install-banner:not(.hidden) {
  display: flex;
}
</style>

<div id="install-banner" x-data="{ show: false }" :class="{ 'hidden': !show }" style="
  position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
  max-width: 440px; width: calc(100% - 32px);
  background: var(--glass-bg); backdrop-filter: var(--glass-blur);
  border: 1px solid var(--glass-border); border-radius: 16px;
  padding: 14px 16px; z-index: 90;
  align-items: center; justify-content: space-between;
  box-shadow: var(--shadow-md);">
  <div>
    <p style="font-family: 'Nunito', sans-serif; font-weight: 600;
              font-size: 0.875rem; color: var(--ink); margin: 0;">
      Install The Muhsinat Club on your device for faster access.
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-shrink: 0;">
    <button @click="installPWA(); show = false" class="btn btn-gold btn-sm">Install</button>
    <button @click="show = false; localStorage.setItem('tmc_install_dismissed_v2', Date.now().toString())" class="btn btn-sm" style="background:transparent;color:var(--ink-soft);border:1px solid var(--border);">Not now</button>
  </div>
</div>

<div id="ios-install-banner" x-data="{ show: false }"
  :class="{ 'hidden': !show }"
  @open-ios-install-instructions.window="show = true"
  style="
    position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
    max-width: 440px; width: calc(100% - 32px);
    background: var(--glass-bg); backdrop-filter: var(--glass-blur);
    border: 1px solid var(--glass-border); border-radius: 16px;
    padding: 14px 16px; z-index: 90;
    align-items: center; justify-content: space-between;
    box-shadow: var(--shadow-md);">
  <div>
    @include('partials.ios-install-instructions')
  </div>
  <button @click="show = false; localStorage.setItem('tmc_ios_install_dismissed_v2', Date.now().toString())" style="background:none;
    border:none; color: var(--ink-soft); font-size: 18px; cursor: pointer; flex-shrink: 0;">&times;</button>
</div>

<script data-navigate-once>
const VAPID_PUBLIC_KEY = '{{ config('services.webpush.public_key') }}';

window.__tmcInstall = window.__tmcInstall || { prompt: null, available: false };

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

function isAlreadyStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
}

function isDismissedRecently(key) {
  const val = localStorage.getItem(key);
  if (!val) return false;
  const ts = parseInt(val, 10);
  if (isNaN(ts)) return false;
  return (Date.now() - ts) < (30 * 24 * 60 * 60 * 1000);
}

function isIOS() {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

function isInStandaloneMode() {
  return ('standalone' in window.navigator) && window.navigator.standalone;
}

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').then(reg => {
    let visits = parseInt(localStorage.getItem('tmc_visits') || '0') + 1;
    localStorage.setItem('tmc_visits', visits);

    if (visits >= 2 && Notification.permission === 'default') {
      requestPushPermission(reg);
    } else if (Notification.permission === 'granted') {
      subscribeToPush(reg);
    }
  }).catch(() => {});
}

async function requestPushPermission(reg) {
  const perm = await Notification.requestPermission();
  if (perm === 'granted') subscribeToPush(reg);
}

async function subscribeToPush(reg) {
  try {
    const existing = await reg.pushManager.getSubscription();
    const sub = existing || await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
    });

    await fetch('/push/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
      },
      body: JSON.stringify(sub)
    });
  } catch (e) {
    console.warn('Push subscription failed:', e);
  }
}

window.addEventListener('beforeinstallprompt', (e) => {
  if (isAlreadyStandalone()) return;

  e.preventDefault();
  window.__tmcInstall.prompt = e;
  window.__tmcInstall.available = true;

  if (isDismissedRecently('tmc_install_dismissed_v2')) return;

  let installVisits = parseInt(localStorage.getItem('tmc_install_visits') || '0') + 1;
  localStorage.setItem('tmc_install_visits', installVisits);

  if (installVisits >= 2) {
    const el = document.getElementById('install-banner');
    if (el && typeof Alpine !== 'undefined') {
      Alpine.$data(el).show = true;
    }
  }
});

function installPWA() {
  if (!window.__tmcInstall.prompt) {
    console.warn('installPWA: deferredPrompt not captured — app may not be installable (check manifest icons, HTTPS, service worker).');
    const el = document.getElementById('install-banner');
    if (el && typeof Alpine !== 'undefined') Alpine.$data(el).show = false;
    return;
  }
  window.__tmcInstall.prompt.prompt();
  window.__tmcInstall.prompt.userChoice.then(() => {
    window.__tmcInstall.prompt = null;
    window.__tmcInstall.available = false;
    const el = document.getElementById('install-banner');
    if (el && typeof Alpine !== 'undefined') Alpine.$data(el).show = false;
  }).catch(() => {
    window.__tmcInstall.prompt = null;
    window.__tmcInstall.available = false;
  });
}

window.addEventListener('appinstalled', () => {
  window.__tmcInstall.prompt = null;
  window.__tmcInstall.available = false;
  const androidBanner = document.getElementById('install-banner');
  const iosBanner = document.getElementById('ios-install-banner');
  if (androidBanner && typeof Alpine !== 'undefined') Alpine.$data(androidBanner).show = false;
  if (iosBanner && typeof Alpine !== 'undefined') Alpine.$data(iosBanner).show = false;
});

if (isIOS() && !isInStandaloneMode() && !isAlreadyStandalone()) {
  if (isDismissedRecently('tmc_ios_install_dismissed_v2')) {
    // dismissed — do nothing
  } else {
    let iosVisits = parseInt(localStorage.getItem('tmc_ios_visits') || '0') + 1;
    localStorage.setItem('tmc_ios_visits', iosVisits);
    if (iosVisits >= 2) {
      const el = document.getElementById('ios-install-banner');
      if (el && typeof Alpine !== 'undefined') {
        Alpine.$data(el).show = true;
      }
    }
  }
}

</script>
</body>
</html>
