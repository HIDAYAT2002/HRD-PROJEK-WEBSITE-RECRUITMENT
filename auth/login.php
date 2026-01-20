<?php
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Login HRD | WGI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <style>
    :root{
      --bg1:#0b1020;
      --bg2:#1a1140;
      --card: rgba(12, 14, 26, .62);
      --card2: rgba(12, 14, 26, .35);
      --text:#e9e8ff;
      --muted: rgba(233,232,255,.72);
      --line: rgba(201, 28, 22, 0.75);
      --line2: rgba(232, 12, 48, 0.55);
      --btn:#cb000e;
      --btn2:#cd110a;
      --danger:#fb7185;
      --shadow: 0 24px 80px rgba(0,0,0,.55);
      --radius: 18px;

            --bg-image: url("../assets/bg-login.jpg");
    }

    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
      color: var(--text);
      overflow-x:hidden;
      background:
        radial-gradient(1200px 600px at 60% 20%, rgba(195, 29, 21, 0.28), transparent 60%),
        radial-gradient(900px 500px at 20% 40%, rgba(187, 16, 16, 0.22), transparent 55%),
        linear-gradient(180deg, var(--bg2), var(--bg1));
    }

    /* background photo + overlay */
    .page{
      min-height:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 24px 14px;
      position:relative;
      isolation:isolate;
    }

    .page::before{
      content:"";
      position:absolute;
      inset:0;
      z-index:-2;
      background-image: var(--bg-image);
      background-size: cover;
      background-position: center;
      filter: saturate(1.05) contrast(1.03);
      opacity:.95;
    }

    .page::after{
      content:"";
      position:absolute;
      inset:0;
      z-index:-1;
      background:
        linear-gradient(180deg, rgba(15,10,40,.55), rgba(5,8,18,.72)),
        radial-gradient(900px 420px at 20% 15%, rgba(187, 42, 13, 0.25), transparent 55%),
        radial-gradient(900px 520px at 80% 25%, rgba(99,102,241,.22), transparent 55%);
      backdrop-filter: blur(0px);
    }

    /* streak lines (kaya gambar) */
    .streak{
      position:absolute;
      inset:-40px;
      z-index:-1;
      pointer-events:none;
      opacity:.7;
    }
    .streak::before,
    .streak::after{
      content:"";
      position:absolute;
      width: 520px;
      height: 2px;
      background: linear-gradient(90deg, transparent, rgba(233,232,255,.55), transparent);
      transform: rotate(-12deg);
      filter: blur(.3px);
      top: 18%;
      left: 58%;
    }
    .streak::after{
      width: 420px;
      top: 23%;
      left: 66%;
      opacity:.6;
      transform: rotate(-10deg);
    }

    /* card */
    .auth-card{
      width: 100%;
      max-width: 420px;
      border-radius: var(--radius);
      background: linear-gradient(180deg, var(--card), var(--card2));
      box-shadow: var(--shadow);
      border: 1px solid rgba(255,255,255,.10);
      backdrop-filter: blur(14px);
      padding: 22px 20px 18px;
      position:relative;
    }

    .auth-card::before{
      content:"";
      position:absolute;
      inset: -1px;
      border-radius: calc(var(--radius) + 1px);
      padding: 1px;
      background: linear-gradient(135deg, rgba(197, 6, 6, 0.45), rgba(99,102,241,.20), rgba(255,255,255,.08));
      -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
      -webkit-mask-composite: xor;
              mask-composite: exclude;
      pointer-events:none;
      opacity:.85;
    }

    .brand{
      text-align:center;
      margin-bottom: 10px;
    }
    .brand h1{
      margin: 2px 0 0;
      font-size: 18px;
      letter-spacing: .8px;
      font-weight: 800;
    }
    .brand p{
      margin: 6px 0 0;
      font-size: 12.5px;
      color: var(--muted);
    }

    .title{
      text-align:center;
      margin: 10px 0 14px;
      font-size: 18px;
      font-weight: 900;
      letter-spacing:.9px;
    }
    .title-line{
      height: 2px;
      width: 72%;
      margin: 10px auto 0;
      background: linear-gradient(90deg, transparent, rgba(183, 10, 10, 0.9), transparent);
      border-radius: 99px;
    }

    /* inputs ala garis neon */
    .field{
      margin-top: 12px;
    }
    .label{
      display:block;
      font-size: 11px;
      color: rgba(233,232,255,.78);
      letter-spacing:.4px;
      margin-bottom: 6px;
    }
    .input{
      width:100%;
      background: transparent;
      border: 0;
      outline: none;
      color: var(--text);
      padding: 10px 2px 12px;
      font-size: 14px;
      letter-spacing:.2px;
    }
    .underline{
      height: 2px;
      width: 100%;
      border-radius: 99px;
      background: linear-gradient(90deg, rgba(167,139,250,.95), rgba(99,102,241,.35));
      opacity:.9;
    }
    .field:focus-within .underline{
      background: linear-gradient(90deg, rgba(168,85,247,1), rgba(99,102,241,.55));
      box-shadow: 0 0 0 4px rgba(168,85,247,.12);
    }

    .row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin-top: 10px;
    }
    .hint{
      font-size: 12px;
      color: rgba(233,232,255,.70);
    }
    .link{
      font-size: 12px;
      color: rgba(233,232,255,.78);
      text-decoration:none;
    }
    .link:hover{ text-decoration: underline; }

    .btn{
      width: 100%;
      margin-top: 14px;
      border: 0;
      cursor:pointer;
      padding: 11px 14px;
      border-radius: 999px;
      font-weight: 800;
      letter-spacing:.3px;
      color: white;
      background: linear-gradient(90deg, var(--btn), var(--btn2));
      box-shadow: 0 16px 40px rgba(124,58,237,.28);
      transition: transform .08s ease, filter .12s ease;
    }
    .btn:active{ transform: translateY(1px) scale(.995); }
    .btn:hover{ filter: brightness(1.06); }

    .divider{
      margin: 14px 0 10px;
      height:1px;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
    }

    /* tombol kembali ala pill tapi outline */
    .btn-back{
      display:block;
      text-align:center;
      margin-top: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      font-size: 13px;
      color: rgba(233,232,255,.88);
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.14);
      transition: background .12s ease, transform .08s ease;
    }
    .btn-back:hover{
      background: rgba(255,255,255,.07);
    }
    .btn-back:active{ transform: translateY(1px); }

    .footer{
      text-align:center;
      margin-top: 12px;
      font-size: 11px;
      color: rgba(233,232,255,.55);
    }

    /* kecilin card di hp */
    @media (max-width: 420px){
      .auth-card{ padding: 20px 16px 16px; }
      .title{ font-size: 17px; }
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="streak"></div>

    <div class="auth-card">
      <div class="brand">
        <h1>PT WGI CAREER</h1>
              </div>

      <div class="title">
        LOGIN
        <div class="title-line"></div>
      </div>

      <form action="proses_login.php" method="POST" autocomplete="on">
        <div class="field">
          <label class="label" for="email">Email</label>
          <input class="input" id="email" type="email" name="email" placeholder="Masukkan email" required />
          <div class="underline"></div>
        </div>

        <div class="field">
          <label class="label" for="password">Password</label>
          <input class="input" id="password" type="password" name="password" placeholder="Masukkan password" required />
          <div class="underline"></div>
        </div>

        <div class="row">
          <span class="hint">Pastikan email &amp; password benar.</span>
          <!-- kalau kamu punya halaman lupa password, tinggal arahkan -->
          <!-- <a class="link" href="lupa_password.php">Lupa password?</a> -->
        </div>

        <button class="btn" type="submit">Masuk</button>
      </form>

      <div class="divider"></div>

      <a href="../index.php" class="btn-back">← Kembali ke Halaman Depan</a>

      <div class="footer">© <?= date('Y'); ?> PT Wiraswasta Gemilang Indonesia</div>
    </div>
  </div>
</body>
</html>
