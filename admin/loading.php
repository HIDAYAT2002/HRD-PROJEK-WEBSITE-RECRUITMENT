<?php
// loading.php (FULL) - Loader "gangsing" tanpa database
// Pakai: include 'loading.php'; di index.php (tepat setelah <body>)

$LOADER_LOGO = $LOADER_LOGO ?? "../assets/img/evalube_logo.png"; // bisa dioverride sebelum include
$LOADER_TEXT = $LOADER_TEXT ?? "Memuat halaman...";
?>
<style>
  :root{
    --loader-bg: rgba(2,6,23,.80);
    --loader-glow: rgba(34,197,94,.22);
    --loader-blue: rgba(37,99,235,.22);
    --loader-ring: rgba(255,255,255,.12);
    --loader-white: rgba(255,255,255,.92);
  }

  /* overlay */
  .wgi-loader{
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(1200px 700px at 60% 30%, rgba(79,70,229,.20), transparent 60%),
                radial-gradient(900px 500px at 40% 70%, rgba(34,197,94,.14), transparent 55%),
                var(--loader-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: opacity .25s ease, transform .25s ease;
    opacity: 1;
    transform: translateZ(0);
  }
  .wgi-loader.is-hidden{
    opacity: 0;
    pointer-events: none;
    transform: scale(1.01);
  }

  .wgi-loader-card{
    width: min(520px, 92vw);
    border-radius: 26px;
    padding: 24px 22px 20px;
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 30px 100px rgba(0,0,0,.45);
    position: relative;
    overflow: hidden;
  }
  .wgi-loader-card:before{
    content:"";
    position:absolute; inset:-120px -160px auto auto;
    width:420px; height:420px;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.18), rgba(255,255,255,0) 60%);
    transform: rotate(12deg);
    pointer-events:none;
  }

  .wgi-loader-top{
    display:flex;
    gap:18px;
    align-items:center;
  }

  /* gangsing stage */
  .wgi-top-stage{
    width: 140px;
    height: 140px;
    position: relative;
    display:grid;
    place-items:center;
    isolation:isolate;
  }

  .wgi-ring{
    position:absolute;
    inset:-6px;
    border-radius:999px;
    border: 1px solid var(--loader-ring);
    box-shadow:
      0 0 0 1px rgba(255,255,255,.06) inset,
      0 0 40px var(--loader-blue),
      0 0 40px var(--loader-glow);
    filter: blur(.0px);
  }
  .wgi-ring:before{
    content:"";
    position:absolute; inset:10px;
    border-radius:999px;
    border: 1px dashed rgba(255,255,255,.16);
    opacity:.9;
    animation: dashspin 1.8s linear infinite;
  }

  .wgi-shadow{
    position:absolute;
    width: 92px;
    height: 18px;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: radial-gradient(closest-side, rgba(0,0,0,.55), rgba(0,0,0,0));
    filter: blur(2px);
    opacity: .65;
    z-index: 0;
    animation: shadowPulse .9s ease-in-out infinite;
  }

  /* gangsing image spin */
  .wgi-top{
    width: 108px;
    height: 52px;
    object-fit: contain;
    z-index: 2;
    transform-origin: 50% 85%;
    animation: topSpin .75s linear infinite, topBob .9s ease-in-out infinite;
    filter: drop-shadow(0 18px 18px rgba(0,0,0,.35));
    will-change: transform;
  }

  /* little shine orbit */
  .wgi-orbit{
    position:absolute;
    inset: 18px;
    border-radius:999px;
    z-index: 1;
    animation: orbit 1.0s linear infinite;
  }
  .wgi-orbit:before{
    content:"";
    position:absolute;
    width: 10px; height: 10px;
    border-radius:999px;
    background: rgba(255,255,255,.85);
    box-shadow: 0 0 18px rgba(255,255,255,.35);
    left: 50%;
    top: -6px;
    transform: translateX(-50%);
    opacity:.85;
  }

  .wgi-loader-text{
    flex:1;
    min-width: 0;
  }
  .wgi-loader-title{
    margin:0;
    font-weight: 950;
    letter-spacing: .3px;
    color: var(--loader-white);
    font-size: 16px;
    line-height: 1.15;
  }
  .wgi-loader-sub{
    margin-top: 8px;
    font-weight: 850;
    color: rgba(255,255,255,.72);
    font-size: 12px;
    line-height: 1.35;
  }

  /* progress bar fake */
  .wgi-bar{
    margin-top: 16px;
    height: 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.10);
    overflow:hidden;
    border: 1px solid rgba(255,255,255,.12);
  }
  .wgi-bar > i{
    display:block;
    height:100%;
    width: 38%;
    border-radius:999px;
    background: linear-gradient(90deg, rgba(37,99,235,.95), rgba(34,197,94,.95));
    filter: saturate(1.1);
    animation: barMove 1.1s ease-in-out infinite;
  }

  @keyframes topSpin{
    0%   { transform: perspective(700px) rotateX(68deg) rotateZ(0deg)   scale(1.00); }
    100% { transform: perspective(700px) rotateX(68deg) rotateZ(360deg) scale(1.00); }
  }
  @keyframes topBob{
    0%,100% { translate: 0 0; }
    50%     { translate: 0 -2px; }
  }
  @keyframes shadowPulse{
    0%,100% { transform: translateX(-50%) scale(1.00); opacity:.62; }
    50%     { transform: translateX(-50%) scale(1.10); opacity:.76; }
  }
  @keyframes dashspin{
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  @keyframes orbit{
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  @keyframes barMove{
    0%   { transform: translateX(-20%); width: 28%; opacity:.85; }
    50%  { transform: translateX(60%);  width: 42%; opacity:1; }
    100% { transform: translateX(-20%); width: 28%; opacity:.85; }
  }

  @media (max-width:560px){
    .wgi-loader-card{ padding: 18px 16px 16px; border-radius: 22px; }
    .wgi-top-stage{ width: 120px; height: 120px; }
    .wgi-top{ width: 98px; height: 48px; }
  }
</style>

<div class="wgi-loader" id="wgiLoader" aria-hidden="false">
  <div class="wgi-loader-card">
    <div class="wgi-loader-top">
      <div class="wgi-top-stage">
        <div class="wgi-ring"></div>
        <div class="wgi-orbit"></div>
        <img class="wgi-top" src="<?=htmlspecialchars($LOADER_LOGO, ENT_QUOTES)?>" alt="Loading">
        <div class="wgi-shadow"></div>
      </div>

      <div class="wgi-loader-text">
        <p class="wgi-loader-title"><?=htmlspecialchars($LOADER_TEXT, ENT_QUOTES)?></p>
        <div class="wgi-loader-sub">
          Mohon tunggu sebentar… sistem sedang menyiapkan halaman.
        </div>
        <div class="wgi-bar" aria-hidden="true"><i></i></div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const el = document.getElementById("wgiLoader");
  if(!el) return;

  function show(){
    el.classList.remove("is-hidden");
    el.setAttribute("aria-hidden","false");
  }
  function hide(){
    el.classList.add("is-hidden");
    el.setAttribute("aria-hidden","true");
  }

  // 1) Auto hide saat page selesai load
  window.addEventListener("load", () => {
    setTimeout(hide, 180); // dikit biar animasi keliatan halus
  });

  // 2) Tampil lagi saat pindah halaman (klik link internal)
  document.addEventListener("click", (e) => {
    const a = e.target && e.target.closest ? e.target.closest("a") : null;
    if(!a) return;

    // abaikan: target blank, anchor #, download, javascript:, mailto:, tel:
    const href = a.getAttribute("href") || "";
    if(a.target === "_blank") return;
    if(a.hasAttribute("download")) return;
    if(!href || href.startsWith("#")) return;
    if(href.startsWith("javascript:")) return;
    if(href.startsWith("mailto:") || href.startsWith("tel:")) return;

    // kalau beda host, jangan ganggu
    try{
      const u = new URL(href, location.href);
      if(u.origin !== location.origin) return;
      show();
    }catch(err){}
  }, true);

  // 3) Tampil saat submit form
  document.addEventListener("submit", () => show(), true);

  // expose manual control
  window.WGILoader = { show, hide };
})();
</script>
