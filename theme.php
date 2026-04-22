<?php
// theme.php — shared <head> + <body> opening for dark design
// Expects: $page_title (optional)

if (!defined('SITE_NAME')) require_once __DIR__ . '/config.php';
$page_title = $page_title ?? SITE_NAME;
?>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        bg:   { base:'#060810', card:'#0D0F1A', soft:'#141726', hover:'#1C2032' },
        line: '#1D2236',
        txt:  { primary:'#F4F4F5', secondary:'#A1A1AA', muted:'#71717A' },
        cy:   { DEFAULT:'#22D3EE', dark:'#06B6D4', soft:'rgba(34,211,238,0.10)', border:'rgba(34,211,238,0.22)' },
        vi:   { DEFAULT:'#A78BFA', dark:'#8B5CF6', soft:'rgba(167,139,250,0.10)' },
        emr:  '#10B981', danger:'#EF4444', warn:'#F59E0B',
      },
      fontFamily: { sans: ['Inter','system-ui','sans-serif'] },
      boxShadow: {
        card: '0 1px 0 rgba(255,255,255,0.02) inset, 0 8px 40px rgba(0,0,0,0.65)',
        glow: '0 0 0 1px rgba(34,211,238,0.2), 0 8px 32px rgba(34,211,238,0.18)',
      }
    }
  }
}
</script>
<style>
  :root { color-scheme: dark; }
  body { font-family: 'Inter', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }

  /* Aurora */
  .aurora { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
  .ab { position: absolute; border-radius: 50%; }
  .ab-1 { top: -18%; right: -4%; width: 920px; height: 920px; background: radial-gradient(circle, rgba(34,211,238,0.16), transparent 62%); animation: aurA 28s ease-in-out infinite alternate; }
  .ab-2 { bottom: -14%; left: -8%; width: 740px; height: 740px; background: radial-gradient(circle, rgba(167,139,250,0.14), transparent 62%); animation: aurB 36s ease-in-out infinite alternate; }
  .ab-3 { top: 28%; left: 32%; width: 640px; height: 640px; background: radial-gradient(circle, rgba(99,102,241,0.08), transparent 60%); animation: aurC 46s ease-in-out infinite alternate; }
  @keyframes aurA { 0%{transform:translate(0,0);} 100%{transform:translate(-170px, 100px);} }
  @keyframes aurB { 0%{transform:translate(0,0);} 100%{transform:translate(130px, -90px);} }
  @keyframes aurC { 0%{transform:translate(0,0) scale(1);} 100%{transform:translate(-60px, 50px) scale(1.18);} }

  /* Grid overlay */
  .grid-bg {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image:
      linear-gradient(rgba(255,255,255,0.030) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.030) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: radial-gradient(ellipse at 50% 0%, black 0%, transparent 74%);
    -webkit-mask-image: radial-gradient(ellipse at 50% 0%, black 0%, transparent 74%);
  }

  /* Gradient border */
  .gborder { position: relative; }
  .gborder::before {
    content: ''; position: absolute; inset: 0; border-radius: inherit; padding: 1px;
    background: linear-gradient(135deg, rgba(34,211,238,0.55), rgba(167,139,250,0.35) 55%, rgba(255,255,255,0.03) 85%);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    pointer-events: none; animation: borderPulse 4s ease-in-out infinite;
  }
  @keyframes borderPulse { 0%,100%{opacity:0.65;} 50%{opacity:1;} }

  /* Spotlight */
  .spot { position: relative; overflow: hidden; isolation: isolate; }
  .spot::after {
    content: ''; position: absolute; inset: 0; border-radius: inherit;
    background: radial-gradient(350px circle at var(--mx,50%) var(--my,50%), rgba(34,211,238,0.11), transparent 50%);
    opacity: 0; transition: opacity .3s; pointer-events: none; z-index: 0;
  }
  .spot:hover::after { opacity: 1; }
  .spot > * { position: relative; z-index: 1; }

  /* Shimmer text */
  .shimmer-text {
    background: linear-gradient(90deg, #22D3EE 0%, #A78BFA 35%, #22D3EE 65%, #A78BFA 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shimmer 5s linear infinite;
  }
  @keyframes shimmer { 0%{background-position:0% center;} 100%{background-position:200% center;} }

  /* Reveal */
  .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s cubic-bezier(.2,.8,.2,1), transform .65s cubic-bezier(.2,.8,.2,1); }
  .reveal.in { opacity: 1; transform: none; }
  .reveal[data-d="1"] { transition-delay: .08s; }
  .reveal[data-d="2"] { transition-delay: .17s; }
  .reveal[data-d="3"] { transition-delay: .26s; }
  .reveal[data-d="4"] { transition-delay: .35s; }
  .reveal[data-d="5"] { transition-delay: .44s; }

  /* Fade in */
  .fade-in { animation: fadeIn .75s cubic-bezier(.2,.8,.2,1) both; }
  .fd1 { animation-delay: .1s; }
  .fd2 { animation-delay: .22s; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }

  /* Swap rotate */
  .swap-r { transition: transform .38s ease; }
  .swap-r:hover { transform: rotate(180deg); }

  /* Pulse dot */
  .pdot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10B981; box-shadow: 0 0 0 0 rgba(16,185,129,0.7); animation: pdot 2s infinite; }
  @keyframes pdot { 0%{box-shadow:0 0 0 0 rgba(16,185,129,0.7);} 70%{box-shadow:0 0 0 9px rgba(16,185,129,0);} 100%{box-shadow:0 0 0 0 rgba(16,185,129,0);} }

  /* Step badge */
  .step-num { width: 34px; height: 34px; border-radius: 50%; border: 1px solid rgba(34,211,238,0.35); background: rgba(34,211,238,0.08); color: #22D3EE; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; position: relative; margin-bottom: 16px; }
  .step-num::after { content: ''; position: absolute; inset: -5px; border-radius: 50%; border: 1px solid rgba(34,211,238,0.18); animation: stepRing 3s ease-in-out infinite; }
  @keyframes stepRing { 0%,100%{transform:scale(1);opacity:0.8;} 50%{transform:scale(1.2);opacity:0;} }

  /* Inputs */
  .field { background: #08091A; border: 1px solid #1D2236; transition: border-color .2s; }
  .field:focus-within { border-color: rgba(34,211,238,0.5); }
  .input-d { background: #08091A; border: 1px solid #1D2236; color: #F4F4F5; transition: border-color .2s, box-shadow .2s; }
  .input-d::placeholder { color: #52525B; }
  .input-d:focus { outline: none; border-color: rgba(34,211,238,0.6); box-shadow: 0 0 0 3px rgba(34,211,238,0.10); }
  select.input-d option { background: #0D0F1A; color: #F4F4F5; }

  /* Buttons */
  .btn-cy { background: linear-gradient(135deg, #22D3EE, #06B6D4); color: #060810; transition: box-shadow .25s, transform .2s; }
  .btn-cy:hover { box-shadow: 0 0 0 1px rgba(34,211,238,0.35), 0 10px 42px rgba(34,211,238,0.42); transform: translateY(-1px); }
  .btn-cy:active { transform: translateY(0); }
  .btn-cy:disabled { opacity: 0.45; cursor: not-allowed; background: #1C2032; color: #71717A; transform: none; box-shadow: none; }
  .btn-vi { background: linear-gradient(135deg, #A78BFA, #8B5CF6); color: #060810; transition: box-shadow .25s, transform .2s; }
  .btn-vi:hover { box-shadow: 0 0 0 1px rgba(167,139,250,0.35), 0 10px 42px rgba(167,139,250,0.42); transform: translateY(-1px); }
  .btn-ghost { background: transparent; border: 1px solid #1D2236; color: #F4F4F5; transition: all .2s; }
  .btn-ghost:hover { border-color: rgba(34,211,238,0.4); background: rgba(34,211,238,0.05); color: #22D3EE; }
  .btn-danger { background: rgba(239,68,68,0.1); color: #EF4444; border: 1px solid rgba(239,68,68,0.25); transition: all .2s; }
  .btn-danger:hover { background: rgba(239,68,68,0.18); border-color: rgba(239,68,68,0.4); }

  /* Tag */
  .tag-h { transition: border-color .2s, transform .2s, background .2s; }
  .tag-h:hover { border-color: rgba(34,211,238,0.38) !important; transform: translateY(-1px); background: rgba(34,211,238,0.05) !important; }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-track { background: #060810; }
  ::-webkit-scrollbar-thumb { background: #1D2236; border-radius: 4px; }
  ::-webkit-scrollbar-thumb:hover { background: #2A2F45; }

  /* Status badges */
  .st { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; }
  .st-new    { background: rgba(245,158,11,0.12); color: #F59E0B; border: 1px solid rgba(245,158,11,0.3); }
  .st-proc   { background: rgba(34,211,238,0.12); color: #22D3EE; border: 1px solid rgba(34,211,238,0.3); }
  .st-ok     { background: rgba(16,185,129,0.12); color: #10B981; border: 1px solid rgba(16,185,129,0.3); }
  .st-cancel { background: rgba(239,68,68,0.12); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }

  /* Table rows */
  .row-h:hover { background: rgba(255,255,255,0.025); }

  /* Toast */
  .toast-w { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 14px 26px; border-radius: 12px; color: #F4F4F5; font-weight: 600; box-shadow: 0 10px 40px rgba(0,0,0,0.5); opacity: 0; transition: all .4s ease; min-width: 280px; text-align: center; border: 1px solid; backdrop-filter: blur(12px); }
  .toast-w.show { opacity: 1; top: 30px; }
  .toast-w.success { background: rgba(16,185,129,0.15); border-color: rgba(16,185,129,0.4); color: #34D399; }
  .toast-w.error   { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.4); color: #F87171; }
  .toast-w.info    { background: rgba(34,211,238,0.15); border-color: rgba(34,211,238,0.4); color: #22D3EE; }

  /* Highlight row */
  @keyframes rowHighlight { 0%{background:rgba(34,211,238,0.18);} 100%{background:transparent;} }
  .highlight-row { animation: rowHighlight 3s ease; }

  /* === Mobile-first responsive adjustments === */
  @media (max-width: 768px) {
    /* Smaller, less-heavy aurora for perf */
    .ab-1 { width: 480px; height: 480px; top: -10%; right: -12%; }
    .ab-2 { width: 420px; height: 420px; bottom: -12%; left: -16%; }
    .ab-3 { width: 360px; height: 360px; }
    .grid-bg { background-size: 40px 40px; }

    /* Step badge a touch smaller */
    .step-num { width: 30px; height: 30px; font-size: 12px; margin-bottom: 12px; }

    /* Toast smaller margins on phone */
    .toast-w { min-width: 0; left: 12px; right: 12px; transform: none; width: calc(100% - 24px); padding: 12px 18px; font-size: 14px; }
    .toast-w.show { top: 12px; }

    /* Ensure selects/inputs are tap-friendly (prevent iOS zoom on <16px) */
    select.input-d, input.input-d { font-size: 16px; }
  }

  /* Never let anything force horizontal page scroll */
  html, body { max-width: 100%; overflow-x: hidden; }
</style>