const nav = document.getElementById('site-nav');
const progress = document.getElementById('progress');
const year = document.getElementById('year');
const preloader = document.getElementById('preloader');
let preloaderHidden = false;
year.textContent = new Date().getFullYear();

function hidePreloader() {
  if (!preloader || preloaderHidden) return;
  preloaderHidden = true;
  document.body.classList.remove('loading');
  preloader.classList.add('hidden');
  setTimeout(() => preloader.remove(), 850);
}
window.addEventListener('load', () => setTimeout(hidePreloader, 200));
setTimeout(hidePreloader, 1500);

function updateChrome() {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  const pct = max > 0 ? (window.scrollY / max) * 100 : 0;
  nav.classList.toggle('scrolled', window.scrollY > 60);
  progress.style.width = pct + '%';
}
updateChrome();
window.addEventListener('scroll', updateChrome, { passive: true });

// Band marquee
const bandItems = ['Daily Reflections', 'Jannah Coins', 'Private Journal', 'Member Souq', 'Halaqahs', 'Legacy Card', 'Faith Community'];
const bandTrack = document.getElementById('band-track');
const bandHtml = bandItems.map(item => `<span class="band-item">\u2726 ${item}</span>`).join('');
bandTrack.innerHTML = bandHtml + bandHtml;

// Scroll reveal
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('visible');
    revealObserver.unobserve(entry.target);
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => revealObserver.observe(el));

// Steps stagger
const stepObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const steps = Array.from(document.querySelectorAll('.step'));
    const delay = steps.indexOf(entry.target) * 200;
    setTimeout(() => entry.target.classList.add('visible'), delay);
    stepObserver.unobserve(entry.target);
  });
}, { threshold: 0.2 });
document.querySelectorAll('.step').forEach(step => stepObserver.observe(step));

// Stat counters
const statObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target;
    const target = Number(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    const duration = 1800;
    const start = performance.now();
    function tick(now) {
      const p = Math.min((now - start) / duration, 1);
      el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    statObserver.unobserve(el);
  });
}, { threshold: 0.5 });
document.querySelectorAll('[data-count]').forEach(el => statObserver.observe(el));

// Custom cursor
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
if (finePointer) {
  const dot = document.getElementById('cursor-dot');
  const ring = document.getElementById('cursor-ring');
  let mx = innerWidth / 2, my = innerHeight / 2, rx = mx, ry = my;
  document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; dot.style.left = mx + 'px'; dot.style.top = my + 'px'; });
  (function loop() { rx += (mx - rx) * 0.12; ry += (my - ry) * 0.12; ring.style.left = rx + 'px'; ring.style.top = ry + 'px'; requestAnimationFrame(loop); })();
  document.querySelectorAll('a, button').forEach(el => {
    el.addEventListener('mouseenter', () => { dot.style.transform = 'translate(-50%,-50%) scale(1.65)'; ring.style.width = '48px'; ring.style.height = '48px'; });
    el.addEventListener('mouseleave', () => { dot.style.transform = 'translate(-50%,-50%) scale(1)'; ring.style.width = '32px'; ring.style.height = '32px'; });
  });
}