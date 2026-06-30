<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#1A6B72">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
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
    <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;">
      <img src="{{ asset('images/img1.png') }}"
           alt="TMC"
           style="width:32px;height:32px;object-fit:contain;">
    </a>
    <span class="text-[11px] font-medium text-teal-dk/70" style="font-feature-settings:'tnum';">
      {{ now()->hijri('j M Y') }}
    </span>
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

<livewire:announcement-popup />

@livewireScripts

<div id="install-banner" class="hidden" style="
  position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
  max-width: 440px; width: calc(100% - 32px);
  background: var(--glass-bg); backdrop-filter: var(--glass-blur);
  border: 1px solid var(--glass-border); border-radius: 16px;
  padding: 14px 16px; z-index: 90;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: var(--shadow-md);">
  <div>
    <p style="font-family: 'Nunito', sans-serif; font-weight: 600;
              font-size: 0.875rem; color: var(--ink); margin: 0;">
      Add TMC to your home screen
    </p>
    <p style="font-family: 'Nunito', sans-serif; font-weight: 300;
              font-size: 0.75rem; color: var(--ink-soft); margin: 0;">
      Quick access, just like an app
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button onclick="installPWA()" class="btn btn-gold btn-sm">Install</button>
    <button onclick="dismissInstallBanner()" style="background:none;
      border:none; color: var(--ink-soft); font-size: 18px; cursor: pointer;">&times;</button>
  </div>
</div>

<div id="ios-install-banner" class="hidden" style="
  position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
  max-width: 440px; width: calc(100% - 32px);
  background: var(--glass-bg); backdrop-filter: var(--glass-blur);
  border: 1px solid var(--glass-border); border-radius: 16px;
  padding: 14px 16px; z-index: 90;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: var(--shadow-md);">
  <div>
    <p style="font-family: 'Nunito', sans-serif; font-weight: 600;
              font-size: 0.875rem; color: var(--ink); margin: 0;">
      Add TMC to your home screen
    </p>
    <p style="font-family: 'Nunito', sans-serif; font-weight: 300;
              font-size: 0.75rem; color: var(--ink-soft); margin: 0;">
      Tap <strong>Share</strong> then <strong>"Add to Home Screen"</strong>
    </p>
  </div>
  <button onclick="dismissIOSBanner()" style="background:none;
    border:none; color: var(--ink-soft); font-size: 18px; cursor: pointer; flex-shrink: 0;">&times;</button>
</div>

<script>
const VAPID_PUBLIC_KEY = '{{ config('services.webpush.public_key') }}';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
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
  });
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

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;

  let installVisits = parseInt(localStorage.getItem('tmc_install_visits') || '0') + 1;
  localStorage.setItem('tmc_install_visits', installVisits);

  if (installVisits >= 3 && !localStorage.getItem('tmc_install_dismissed')) {
    document.getElementById('install-banner')?.classList.remove('hidden');
  }
});

function installPWA() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt = null;
  }
  document.getElementById('install-banner')?.classList.add('hidden');
}

function dismissInstallBanner() {
  localStorage.setItem('tmc_install_dismissed', '1');
  document.getElementById('install-banner')?.classList.add('hidden');
}

function isIOS() {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

function isInStandaloneMode() {
  return ('standalone' in window.navigator) && window.navigator.standalone;
}

if (isIOS() && !isInStandaloneMode() &&
    !localStorage.getItem('tmc_ios_install_dismissed')) {
  let iosVisits = parseInt(localStorage.getItem('tmc_ios_visits') || '0') + 1;
  localStorage.setItem('tmc_ios_visits', iosVisits);
  if (iosVisits >= 2) {
    document.getElementById('ios-install-banner')?.classList.remove('hidden');
  }
}

function dismissIOSBanner() {
  localStorage.setItem('tmc_ios_install_dismissed', '1');
  document.getElementById('ios-install-banner')?.classList.add('hidden');
}
</script>
</body>
</html>
