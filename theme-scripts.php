<script>
// Lucide icons
if (typeof lucide !== 'undefined') lucide.createIcons();

/* Particles */
(function () {
  if (window.__particlesBooted) return;
  window.__particlesBooted = true;
  const cv = document.createElement('canvas');
  cv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;opacity:0.5';
  document.body.prepend(cv);
  const ctx = cv.getContext('2d');
  let W, H, pts = [];
  function resize() { W = cv.width = innerWidth; H = cv.height = innerHeight; }
  const COLS = ['#22D3EE', '#A78BFA', '#ffffff', '#22D3EE', '#ffffff', '#A78BFA'];
  class Pt {
    constructor(init) { this.reset(init); }
    reset(init) {
      this.x = Math.random() * W;
      this.y = init ? Math.random() * H : H + 4;
      this.r = Math.random() * 1.1 + 0.3;
      this.vx = (Math.random() - .5) * .22;
      this.vy = -(Math.random() * .45 + .1);
      this.a = Math.random() * .32 + .05;
      this.c = COLS[Math.floor(Math.random() * COLS.length)];
    }
    tick() { this.x += this.vx; this.y += this.vy; if (this.y < -4) this.reset(false); }
    draw() { ctx.beginPath(); ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2); ctx.fillStyle = this.c; ctx.globalAlpha = this.a; ctx.fill(); }
  }
  resize();
  pts = Array.from({ length: 55 }, () => new Pt(true));
  window.addEventListener('resize', resize);
  (function loop() { ctx.clearRect(0, 0, W, H); pts.forEach(p => { p.tick(); p.draw(); }); ctx.globalAlpha = 1; requestAnimationFrame(loop); })();
})();

/* Spotlight */
document.querySelectorAll('.spot').forEach(el => {
  el.addEventListener('mousemove', e => {
    const r = el.getBoundingClientRect();
    el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    el.style.setProperty('--my', (e.clientY - r.top) + 'px');
  });
});

/* Scroll reveal */
const __revObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); __revObs.unobserve(e.target); } });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach(el => __revObs.observe(el));

/* Count-up */
function __countUp(el) {
  const target = parseFloat(el.dataset.target);
  const isFloat = !!el.dataset.float;
  const dur = 1800;
  const t0 = performance.now();
  (function tick(now) {
    const p = Math.min((now - t0) / dur, 1);
    const ease = 1 - Math.pow(1 - p, 3);
    el.textContent = isFloat ? (ease * target).toFixed(1) : Math.floor(ease * target).toLocaleString('ru');
    if (p < 1) requestAnimationFrame(tick);
  })(t0);
}
const __cuObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { __countUp(e.target); __cuObs.unobserve(e.target); } });
}, { threshold: 0.4 });
document.querySelectorAll('.count-up').forEach(el => __cuObs.observe(el));

/* Toast auto-hide */
document.querySelectorAll('.toast-w').forEach(t => {
  setTimeout(() => t.classList.add('show'), 50);
  setTimeout(() => t.classList.remove('show'), 5000);
});
</script>