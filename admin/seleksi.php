<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

$me_email = $_SESSION['email'] ?? ($_SESSION['user']['email'] ?? 'user@wgi.com');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Data Seleksi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    .content{ padding:22px; }

    .head{
      background:linear-gradient(135deg,#4f46e5,#2563eb);
      border-radius:22px;
      padding:22px 22px 18px;
      color:#fff;
      position:relative;
      box-shadow:0 14px 40px rgba(2,6,23,.12);
      margin-bottom:16px;
      overflow:hidden;
    }
    .head:after{
      content:"";
      position:absolute; right:-120px; top:-140px;
      width:420px; height:420px;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.22), rgba(255,255,255,0) 60%);
      transform: rotate(12deg);
      pointer-events:none;
    }
    .head h1{ margin:0; font-size:44px; font-weight:950; line-height:1.05; }
    .head .sub{ margin-top:8px; opacity:.92; font-weight:800; font-size:13px; position:relative; z-index:1; }
    .head .pill{
      margin-top:12px;
      display:inline-flex; align-items:center; gap:8px;
      background:rgba(2,6,23,.18);
      border:1px solid rgba(255,255,255,.20);
      padding:7px 12px;
      border-radius:999px;
      font-weight:950; font-size:12px;
      backdrop-filter: blur(8px);
      position:relative; z-index:1;
    }

    .filterBox{
      background:linear-gradient(180deg,#0b1220,#050b17);
      border-radius:20px;
      padding:18px;
      box-shadow:0 16px 50px rgba(2,6,23,.28);
      margin-bottom:14px;
      color:#e5e7eb;
    }
    .filterGrid{
      display:grid;
      grid-template-columns: 1.25fr .85fr 190px;
      gap:14px;
      align-items:end;
    }
    .fLabel{
      font-size:12px; font-weight:950;
      letter-spacing:.4px;
      margin-bottom:8px;
      color:rgba(255,255,255,.80);
    }
    .fControl{
      width:100%;
      height:44px;
      border-radius:14px;
      border:1px solid rgba(255,255,255,.12);
      background:rgba(255,255,255,.06);
      color:#fff;
      outline:none;
      padding:0 12px;
      font-weight:900;
    }
    .fControl::placeholder{ color:rgba(255,255,255,.55); font-weight:800; }
    select.fControl option{ background:#ffffff !important; color:#0f172a !important; font-weight:800; }

    .btnApply{
      grid-column: 1 / -1;
      height:48px;
      border-radius:16px;
      border:none;
      cursor:pointer;
      background:linear-gradient(135deg,#1d4ed8,#2563eb);
      color:#fff;
      font-weight:950;
      letter-spacing:.3px;
      box-shadow:0 14px 30px rgba(37,99,235,.28);
    }
    .btnApply:hover{ filter:brightness(1.04); }

    .btnExcel{
      height:44px;
      border-radius:14px;
      border:none;
      cursor:pointer;
      background:linear-gradient(135deg,#16a34a,#22c55e);
      color:#fff;
      font-weight:950;
      letter-spacing:.2px;
      box-shadow:0 14px 26px rgba(34,197,94,.20);
      white-space:nowrap;
    }
    .btnExcel:hover{ filter:brightness(1.04); }

    .stageTabs{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:14px;
    }
    .tab{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      min-width:160px;
      flex:1 1 160px;
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.10);
      color:#fff;
      border-radius:16px;
      padding:12px 14px;
      cursor:pointer;
      user-select:none;
      transition:.15s;
      font-weight:950;
    }
    .tab .count{
      display:inline-flex;
      min-width:28px;
      height:24px;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      background:rgba(255,255,255,.14);
      font-size:12px;
      font-weight:950;
    }
    .tab.active{
      background:linear-gradient(135deg,#1d4ed8,#2563eb);
      border-color:rgba(255,255,255,.18);
      box-shadow:0 14px 30px rgba(37,99,235,.25);
    }

    .panel{
      background:#fff;
      border-radius:18px;
      box-shadow:0 14px 40px rgba(2,6,23,.10);
      overflow:hidden;
      margin-top:14px;
    }
    .panelHead{
      padding:14px 16px;
      border-bottom:1px solid #eef2ff;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }
    .panelTitle{ font-weight:950; font-size:18px; color:#0f172a; }
    .panelTools{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
    .miniBtn{
      height:34px;
      padding:0 12px;
      border-radius:12px;
      border:1px solid #e5e7eb;
      background:#f8fafc;
      cursor:pointer;
      font-weight:900;
      font-size:12px;
      white-space:nowrap;
    }
    .miniBtn:hover{ filter:brightness(1.02); }
    .miniBtn.primary{
      border:none;
      background:linear-gradient(135deg,#1d4ed8,#2563eb);
      color:#fff;
      box-shadow:0 12px 22px rgba(37,99,235,.20);
    }
    .bodyPad{ padding:14px; }

    .empty{
      padding:18px;
      color:#64748b;
      font-weight:900;
      background:#f8fafc;
      border:1px dashed #e5e7eb;
      border-radius:14px;
      text-align:center;
    }

    .sf-table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:14px;
      overflow:hidden;
    }
    .sf-table thead th{
      background:#0b6aa8;
      color:#fff;
      font-weight:950;
      padding:12px 10px;
      text-align:center;
      border-right:1px solid rgba(255,255,255,.18);
      white-space:nowrap;
    }
    .sf-table thead th:last-child{ border-right:0; }

    .sf-table tbody td{
      padding:12px 10px;
      border-top:1px solid #e5e7eb;
      vertical-align:middle;
      background:#fff;
    }
    .sf-table tbody tr:hover td{ background:#f8fafc; }

    .sf-no{ text-align:center; font-weight:950; width:70px; }
    .sf-name{
      font-weight:950;
      color:#0f172a;
      letter-spacing:.2px;
      text-transform:uppercase;
      font-size:14px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width:360px;
    }

    .sf-tools, .sf-actions, .sf-upload, .sf-del{
      display:flex;
      gap:10px;
      justify-content:center;
      align-items:center;
      flex-wrap:wrap;
    }

    .btn-sm{
      border:0;
      border-radius:12px;
      padding:10px 14px;
      font-weight:950;
      cursor:pointer;
      user-select:none;
      white-space:nowrap;
    }
    .btn-detail{ background:#0b1220; color:#fff; }
    .btn-note{ background:linear-gradient(135deg,#1d4ed8,#2563eb); color:#fff; }
    .btn-zoom{ background:linear-gradient(135deg,#7c3aed,#a855f7); color:#fff; }

    .zoomStack{ display:flex; flex-direction:column; gap:10px; align-items:center; justify-content:center; }
    .zoomBtn{
      width:92px;
      height:38px;
      border:0;
      border-radius:12px;
      cursor:pointer;
      font-weight:950;
      color:#fff;
      background:linear-gradient(135deg,#7c3aed,#a855f7);
      box-shadow:0 12px 24px rgba(168,85,247,.18);
    }
    .zoomBtn:hover{ filter:brightness(1.03); }

    .zForm{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .zForm .full{ grid-column:1 / -1; }
    .zLabel{ font-size:12px; font-weight:950; color:#0f172a; margin-bottom:6px; }
    .zInput{
      width:100%; height:44px;
      border-radius:14px;
      border:1px solid #e5e7eb;
      background:#fff;
      padding:0 12px;
      font-weight:900;
      outline:none;
    }
    .zInput::placeholder{ color:#94a3b8; font-weight:850; }
    .zPreview{
      background:#0b1220;
      color:#fff;
      border-radius:16px;
      padding:12px;
      font-weight:850;
      white-space:pre-wrap;
      border:1px solid rgba(255,255,255,.08);
    }
    .zActions{ display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .zBtn{
      height:44px; padding:0 14px;
      border-radius:14px;
      border:1px solid #e5e7eb;
      background:#f8fafc;
      cursor:pointer;
      font-weight:950;
    }
    .zBtn.primary{ border:0; background:linear-gradient(135deg,#16a34a,#22c55e); color:#fff; }
    .zBtn.purple{ border:0; background:linear-gradient(135deg,#7c3aed,#a855f7); color:#fff; }
    @media(max-width:720px){ .zForm{ grid-template-columns:1fr; } }

    .sf-icon{
      width:44px;
      height:40px;
      border-radius:12px;
      border:1px solid #e5e7eb;
      background:#fff;
      cursor:pointer;
      font-weight:1000;
      font-size:18px;
      line-height:40px;
      text-align:center;
      user-select:none;
    }
    .sf-icon.ok{ border-color:#16a34a; }
    .sf-icon.no{ border-color:#ef4444; }
    .sf-icon:active{ transform:translateY(1px); }

    .sf-picked{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:9px 12px;
      border-radius:12px;
      border:1px solid #e5e7eb;
      background:#f8fafc;
      font-weight:950;
      color:#0f172a;
      font-size:12px;
      white-space:nowrap;
    }

    td.sf-aksi-td{ text-align:center; }
    td.sf-aksi-td > *{ margin-left:auto; margin-right:auto; }

    .sf-status{
      font-weight:950;
      text-align:center;
      color:#0f172a;
      line-height:1.2;
      white-space:nowrap;
    }
    .sf-status .muted{ opacity:.7; font-weight:900; }

    .delbtn{
      background:#ef4444;
      color:#fff;
      border:0;
      border-radius:12px;
      padding:10px 14px;
      font-weight:950;
      cursor:pointer;
      user-select:none;
      white-space:nowrap;
    }

    .fileLink{
      display:inline-flex;
      text-decoration:none;
      font-weight:900;
      font-size:12px;
      padding:10px 12px;
      border-radius:14px;
      background:rgba(37,99,235,.10);
      border:1px solid rgba(37,99,235,.18);
      color:#1d4ed8;
      max-width:260px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }
    .uploadBtn{
      border:1px solid #e5e7eb;
      background:#f8fafc;
      color:#0f172a;
      border-radius:12px;
      padding:10px 14px;
      font-weight:950;
      cursor:pointer;
      user-select:none;
      white-space:nowrap;
    }

    .tableWrap{ overflow:auto; border-radius:14px; }
    @media(max-width:980px){
      .filterGrid{ grid-template-columns:1fr; }
      .head h1{ font-size:34px; }
      .sf-table thead th{ font-size:12px; }
      .sf-name{ font-size:13px; max-width:220px; }
      .fileLink{ max-width:180px; }
      .btnExcel{ width:100%; height:48px; border-radius:16px; }
    }

    .modalMask{
      position:fixed; inset:0;
      background:rgba(2,6,23,.55);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:9999;
      padding:16px;
    }
    .modalBox{
      width:min(1100px, 100%);
      background:#fff;
      border-radius:18px;
      box-shadow:0 25px 80px rgba(2,6,23,.35);
      overflow:hidden;
      max-height:92vh;
      display:flex;
      flex-direction:column;
    }
    .modalHead{
      padding:14px 16px;
      border-bottom:1px solid #eef2ff;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      font-weight:950;
      background:#0b1220;
      color:#fff;
    }
    .modalClose{
      height:36px;
      padding:0 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.18);
      background:rgba(255,255,255,.10);
      cursor:pointer;
      font-weight:950;
      color:#fff;
    }
    .modalBody{
      padding:14px;
      overflow:auto;
      background:#f8fafc;
    }
    .modalLoading{
      padding:14px;
      border-radius:14px;
      background:#fff;
      border:1px solid #e5e7eb;
      font-weight:900;
      color:#0f172a;
    }

    .noteArea{
      width:100%;
      min-height:240px;
      border-radius:16px;
      border:1px solid #e5e7eb;
      padding:12px;
      outline:none;
      font-weight:850;
      resize:vertical;
    }
    .noteBtn{
      width:100%;
      height:44px;
      border-radius:16px;
      border:none;
      cursor:pointer;
      background:linear-gradient(135deg,#1d4ed8,#2563eb);
      color:#fff;
      font-weight:950;
      margin-top:10px;
    }
    .noteBtn.gray{
      background:#f8fafc;color:#0f172a;border:1px solid #e5e7eb;
    }
  </style>
</head>

<body class="admin-body">
<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="head">
    <h1>Data Seleksi</h1>
    <div class="sub">Selamat datang, <b><?=htmlspecialchars($me_email)?></b> • Audit aman • Hatipun<b>•</b>Senang.</div>
    <div class="pill">Favorit → Initiate Call → Tahapan</div>
  </div>

  <div class="filterBox">
    <div class="filterGrid">
      <div>
        <div class="fLabel">Posisi (sudah termasuk lokasi)</div>
        <select id="posisiSelect" class="fControl">
          <option value="">— pilih posisi —</option>
        </select>
      </div>

      <div>
        <div class="fLabel">Cari Nama</div>
        <input id="cariInput" class="fControl" type="text" placeholder="ketik nama pelamar...">
      </div>

      <div>
        <div class="fLabel">Download</div>
        <button id="btnExcel" class="btnExcel">Download Excel</button>
      </div>

      <button id="btnApply" class="btnApply">Terapkan</button>
    </div>

    <div class="stageTabs" id="stageTabs"></div>
  </div>

  <div class="panel">
    <div class="panelHead">
      <div class="panelTitle" id="panelTitle">Data Favorit</div>
      <div class="panelTools" id="panelTools">
        <button class="miniBtn" id="btnRefresh">Refresh</button>
        <button class="miniBtn primary" id="btnDownload" style="display:none">Download Semua</button>
        <button class="miniBtn" id="btnClear" style="display:none" title="Bersihkan board lowongan ini">Clear</button>
      </div>
    </div>

    <div class="bodyPad">
      <div class="tableWrap">
        <table class="sf-table" id="tblMain">
          <thead id="theadMain"></thead>
          <tbody id="tbodyMain"></tbody>
        </table>
      </div>

      <div id="emptyBox" class="empty" style="display:none;margin-top:12px;">Pilih posisi dulu.</div>
    </div>
  </div>
</div>

<!-- MODAL DETAIL / CV -->
<div class="modalMask" id="detailMask" aria-hidden="true">
  <div class="modalBox" role="dialog" aria-modal="true">
    <div class="modalHead">
      <div id="detailTitle">DETAIL</div>
      <button class="modalClose" id="detailClose">Tutup ✕</button>
    </div>
    <div class="modalBody" id="detailBody">
      <div class="modalLoading">Memuat...</div>
    </div>
  </div>
</div>

<!-- MODAL CATATAN (dipakai hanya Initiate Call) -->
<div class="modalMask" id="noteMask" aria-hidden="true">
  <div class="modalBox" role="dialog" aria-modal="true">
    <div class="modalHead">
      <div id="noteTitle">CATATAN</div>
      <button class="modalClose" id="noteClose">Tutup ✕</button>
    </div>
    <div class="modalBody">
      <div style="font-weight:950;margin-bottom:10px;color:#0f172a;" id="noteMeta">—</div>
      <textarea class="noteArea" id="noteText" placeholder="Tulis catatan panjang di sini..."></textarea>
      <button class="noteBtn" id="noteSave">Simpan</button>
      <button class="noteBtn gray" id="noteCancel">Batal</button>
    </div>
  </div>
</div>

<!-- MODAL ZOOM (khusus Interview HRD & User) -->
<div class="modalMask" id="zoomMask" aria-hidden="true">
  <div class="modalBox" role="dialog" aria-modal="true" style="width:min(760px, 100%)">
    <div class="modalHead">
      <div id="zoomTitle">ZOOM • Interview</div>
      <button class="modalClose" id="zoomClose">Tutup ✕</button>
    </div>
    <div class="modalBody">
      <div style="font-weight:950;margin-bottom:10px;color:#0f172a;" id="zoomMeta">—</div>

      <div class="zForm">
        <div>
          <div class="zLabel">Metode Interview</div>
          <select class="zInput" id="zoomMetode">
            <option value="online">Online (Zoom)</option>
            <option value="offline">Offline</option>
          </select>
        </div>
        <div>
          <div class="zLabel">Tanggal (opsional)</div>
          <input class="zInput" id="zoomTanggal" type="text" placeholder="contoh: 20 Januari 2026">
        </div>
        <div>
          <div class="zLabel">Jam (opsional)</div>
          <input class="zInput" id="zoomJam" type="text" placeholder="contoh: 10:00 WIB">
        </div>

        <div class="full" id="zoomLinkWrap">
          <div class="zLabel">Link Zoom (opsional, isi kalau Online)</div>
          <input class="zInput" id="zoomLink" type="text" placeholder="Tempel link Zoom asli di sini (join_url)  https://zoom.us/j/...">
          <div style="font-size:12px;color:#64748b;margin-top:6px;line-height:1.4;">
            Link Zoom <b>tidak dibuat otomatis</b> (biar tidak terjadi <i>Invalid meeting ID</i>). Kalau interview online, paste link Zoom dari akun Zoom Anda.
          </div>
        </div>

        <div class="full" id="zoomLokasiWrap" style="display:none;">
          <div class="zLabel">Lokasi Interview (opsional, isi kalau Offline)</div>
          <input class="zInput" id="zoomLokasi" type="text" placeholder="contoh: Kantor WGI, Cibitung / alamat lengkap">
        </div>

        <div class="full">
          <div class="zLabel">Preview Pesan</div>
          <div class="zPreview" id="zoomPreview">—</div>
        </div>
      </div>

      <div class="zActions" style="margin-top:12px;">
        <button class="zBtn" id="zoomSave">Simpan</button>
        <button class="zBtn primary" id="zoomSend">Kirim</button>
        <button class="zBtn" id="zoomCancel">Batal</button>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const API = "seleksi_api.php";

  // ✅ TAB FAVORIT BALIK
  const TABS = [
    { key:"Favorit", label:"Data Favorit" },
    { key:"Initiate Call", label:"Initiate Call" },
    { key:"Interview HRD & User", label:"Interview" },
    { key:"Psikotest", label:"Psikotest" },
    { key:"MCU", label:"MCU" },
    { key:"Onboarding", label:"Onboarding" },
  ];

  let selectedLowonganId = "";
  let activeTab = "Favorit";

  const posisiSelect = document.getElementById("posisiSelect");
  const cariInput    = document.getElementById("cariInput");
  const btnApply     = document.getElementById("btnApply");
  const btnExcel     = document.getElementById("btnExcel");

  const stageTabs  = document.getElementById("stageTabs");
  const panelTitle = document.getElementById("panelTitle");

  const btnRefresh = document.getElementById("btnRefresh");
  const btnDownload= document.getElementById("btnDownload");
  const btnClear   = document.getElementById("btnClear");

  const theadMain  = document.getElementById("theadMain");
  const tbodyMain  = document.getElementById("tbodyMain");
  const emptyBox   = document.getElementById("emptyBox");

  const detailMask  = document.getElementById("detailMask");
  const detailClose = document.getElementById("detailClose");
  const detailBody  = document.getElementById("detailBody");
  const detailTitle = document.getElementById("detailTitle");

  const noteMask   = document.getElementById("noteMask");
  const noteClose  = document.getElementById("noteClose");
  const noteCancel = document.getElementById("noteCancel");
  const noteSave   = document.getElementById("noteSave");
  const noteText   = document.getElementById("noteText");
  const noteMeta   = document.getElementById("noteMeta");
  const noteTitle  = document.getElementById("noteTitle");
  let noteContext = null;

  // ZOOM modal (Interview)
  const zoomMask    = document.getElementById("zoomMask");
  const zoomClose   = document.getElementById("zoomClose");
  const zoomCancel  = document.getElementById("zoomCancel");
  const zoomSave    = document.getElementById("zoomSave");
  const zoomSend    = document.getElementById("zoomSend");
  const zoomMeta    = document.getElementById("zoomMeta");
  const zoomTitle   = document.getElementById("zoomTitle");
  const zoomTanggal = document.getElementById("zoomTanggal");
  const zoomJam     = document.getElementById("zoomJam");
  const zoomLink    = document.getElementById("zoomLink");
  const zoomMetode  = document.getElementById("zoomMetode");
  const zoomLokasi  = document.getElementById("zoomLokasi");
  const zoomLinkWrap= document.getElementById("zoomLinkWrap");
  const zoomLokasiWrap= document.getElementById("zoomLokasiWrap");
  const zoomPreview = document.getElementById("zoomPreview");
  let zoomContext = null;

  function qs(obj){ return new URLSearchParams(obj).toString(); }
  async function jget(params){
    const res = await fetch(`${API}?${qs(params)}`, {cache:"no-store"});
    return await res.json();
  }

  //⚠️ pake let biar aman saat dioverride untuk WA hook
  let jpost = async (data) => {
    const res = await fetch(API, {
      method:"POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: qs(data),
      cache:"no-store"
    });
    return await res.json();
  };

  async function uploadExcel({lowongan_id, pelamar_id, stage, file}){
    const fd = new FormData();
    fd.append("action","upload_excel");
    fd.append("lowongan_id", lowongan_id);
    fd.append("pelamar_id", pelamar_id);
    fd.append("stage", stage);
    fd.append("file", file);
    const res = await fetch(API, {method:"POST", body:fd, cache:"no-store"});
    return await res.json();
  }

  function esc(s){
    return String(s ?? "").replace(/[&<>"']/g, m => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[m]));
  }

  function detectMatchName(name){
    const q = (cariInput.value || "").trim().toLowerCase();
    if(!q) return true;
    return String(name||"").toLowerCase().includes(q);
  }

  function showEmpty(msg){
    emptyBox.style.display = "block";
    emptyBox.textContent = msg || "Kosong.";
  }
  function hideEmpty(){ emptyBox.style.display = "none"; }

  function renderTabs(counts = {}, favoritCount = 0){
    stageTabs.innerHTML = "";
    TABS.forEach(t => {
      const btn = document.createElement("div");
      btn.className = "tab" + (t.key===activeTab ? " active" : "");

      let c = 0;
      if(t.key === "Favorit") c = favoritCount || 0;
      else c = counts[t.key] ?? 0;

      btn.innerHTML = `<span>${esc(t.label)}</span><span class="count">${c}</span>`;
      btn.onclick = async () => {
        activeTab = t.key;
        await renderCurrent();
      };
      stageTabs.appendChild(btn);
    });
  }

  async function loadPositions(){
    const r = await jget({action:"list_lowongan"});
    if(!r.ok) return;

    posisiSelect.innerHTML = `
      <option value="">— pilih posisi —</option>
      <option value="all">Semua Posisi</option>
    `;

    (r.items || []).forEach(it => {
      const opt = document.createElement("option");
      opt.value = it.id;
      const loc = (it.kota || "").trim();
      opt.textContent = loc ? `${it.posisi} — ${loc}` : `${it.posisi}`;
      posisiSelect.appendChild(opt);
    });
  }

  posisiSelect.addEventListener("change", () => {
    selectedLowonganId = posisiSelect.value || "";

    if(!selectedLowonganId){
      tbodyMain.innerHTML = "";
      theadMain.innerHTML = "";
      showEmpty("Pilih posisi dulu.");
      return;
    }

    if(selectedLowonganId === "all"){
      tbodyMain.innerHTML = "";
      theadMain.innerHTML = "";
      renderTabs({}, 0);
      showEmpty("Mode Semua Posisi: untuk lihat board, pilih posisi tertentu. Untuk laporan, klik Download Excel.");
      return;
    }
  });

  btnApply.onclick = async () => {
    selectedLowonganId = posisiSelect.value || "";
    if(!selectedLowonganId){ alert("Pilih posisi dulu."); return; }

    if(selectedLowonganId === "all"){
      alert("Untuk menampilkan board tahapan, pilih posisi tertentu. Untuk rekap semua posisi, klik Download Excel.");
      return;
    }

    await refreshAllCountsAndRender();
  };

  btnRefresh.onclick = async () => {
    if(!selectedLowonganId){ alert("Pilih posisi dulu."); return; }
    if(selectedLowonganId === "all"){
      alert("Mode Semua Posisi tidak menampilkan board. Pilih posisi tertentu untuk Refresh board.");
      return;
    }
    await refreshAllCountsAndRender(false);
  };

  // ✅ FIX UTAMA:
  // Kalau TAB = Favorit → Download Excel harus SELURUH TAHAPAN
  btnExcel.onclick = () => {
    const pos = posisiSelect.value || "";
    if(!pos){
      alert("Pilih posisi dulu (atau pilih 'Semua Posisi').");
      return;
    }
    const nama = (cariInput.value || "").trim();

    let stage = activeTab;

    // ✅ Favorit => Seluruh Tahapan
    if(stage === "Favorit") stage = "SELURUH";

    // jaga-jaga label
    if(stage === "Interview") stage = "Interview HRD & User";

    const url = `seleksi_export_excel.php?posisi=${encodeURIComponent(pos)}&nama=${encodeURIComponent(nama)}&stage=${encodeURIComponent(stage)}`;
    window.open(url, "_blank");
  };

  btnClear.onclick = async () => {
    if(!selectedLowonganId){ alert("Pilih posisi dulu."); return; }
    if(selectedLowonganId === "all"){
      alert("Tidak bisa Clear pada mode Semua Posisi.");
      return;
    }
    const yes = confirm("Yakin clear semua kandidat dari semua tahap untuk posisi ini?");
    if(!yes) return;
    const r = await jpost({action:"clear_lowongan", lowongan_id:selectedLowonganId});
    if(!r.ok){ alert(r.error || "Gagal clear."); return; }
    await refreshAllCountsAndRender();
  };

  btnDownload.onclick = () => {}; // disembunyiin

  function openDetail(pelamar_id, nama, mode=""){
    if(!selectedLowonganId || selectedLowonganId === "all") return;
    detailTitle.textContent = `DETAIL • ${nama || ""}`.trim();
    detailMask.style.display = "flex";
    detailMask.setAttribute("aria-hidden","false");
    detailBody.innerHTML = `<div class="modalLoading">Memuat...</div>`;

    const url = `seleksi_detail.php?lowongan_id=${encodeURIComponent(selectedLowonganId)}&pelamar_id=${encodeURIComponent(pelamar_id)}${mode ? `&mode=${encodeURIComponent(mode)}` : ""}`;
    fetch(url, {cache:"no-store"})
      .then(r=>r.text())
      .then(html=>{ detailBody.innerHTML = html; })
      .catch(()=>{ detailBody.innerHTML = `<div class="modalLoading" style="background:#fee2e2;border-color:#fecaca;color:#991b1b;">Gagal load detail.</div>`; });
  }
  function closeDetail(){
    detailMask.style.display = "none";
    detailMask.setAttribute("aria-hidden","true");
  }
  detailClose.onclick = closeDetail;
  detailMask.addEventListener("click", (e)=>{ if(e.target === detailMask) closeDetail(); });

  function openNote(ctx){
    noteContext = ctx;
    noteTitle.textContent = `CATATAN • ${activeTab}`;
    noteMeta.innerHTML = `<b>${esc(ctx.nama||"-")}</b>`;
    noteText.value = "";
    noteMask.style.display = "flex";
    noteMask.setAttribute("aria-hidden","false");

    jget({action:"get_catatan", lowongan_id:selectedLowonganId, pelamar_id: ctx.pelamar_id, stage: activeTab})
      .then(r=>{
        if(r.ok && r.note) noteText.value = (r.note.text || "");
      });
  }
  function closeNote(){
    noteMask.style.display = "none";
    noteMask.setAttribute("aria-hidden","true");
    noteContext = null;
  }
  noteClose.onclick = closeNote;
  noteCancel.onclick = closeNote;
  noteMask.addEventListener("click", (e)=>{ if(e.target === noteMask) closeNote(); });

  noteSave.onclick = async () => {
    if(!noteContext) return;
    const text = noteText.value || "";
    const r = await jpost({
      action:"save_catatan",
      lowongan_id:selectedLowonganId,
      pelamar_id:noteContext.pelamar_id,
      stage: activeTab,
      text
    });
    if(!r.ok){ alert(r.error || "Gagal simpan catatan"); return; }
    closeNote();
    await renderCurrent();
  };

  /* =========================
     ZOOM (Interview)
  ========================= */
  function toggleZoomFields(){
    const metode = (zoomMetode.value || 'online');
    if(metode === 'offline'){
      zoomLokasiWrap.style.display = 'block';
      zoomLinkWrap.style.display = 'none';
    }else{
      zoomLokasiWrap.style.display = 'none';
      zoomLinkWrap.style.display = 'block';
    }
  }

  function mailtoLink(email, subject, body){
    const to = encodeURIComponent(email || '');
    const s  = encodeURIComponent(subject || '');
    const b  = encodeURIComponent(body || '');
    return `mailto:${to}?subject=${s}&body=${b}`;
  }

  
  function setZoomModeUI(){
    const mode = (zoomMetode.value || 'online');
    if(mode === 'offline'){
      zoomLinkWrap.style.display = 'none';
      zoomLokasiWrap.style.display = '';
    }else{
      zoomLinkWrap.style.display = '';
      zoomLokasiWrap.style.display = 'none';
    }
  }

  async function refreshZoomPreview(){
    if(!zoomContext) return;
    const tanggal = (zoomTanggal.value || '').trim();
    const jam     = (zoomJam.value || '').trim();
    const link    = (zoomLink.value || '').trim();
    const metode  = (zoomMetode.value || 'online').trim();
    const lokasi  = (zoomLokasi.value || '').trim();

    const r = await jget({
      action:"zoom_meta",
      lowongan_id: selectedLowonganId,
      pelamar_id: zoomContext.pelamar_id,
      tanggal, jam, link, metode, lokasi
    });

    if(r && r.ok){
      zoomPreview.textContent = r.message || '-';
      zoomContext.hp = (r.pelamar && r.pelamar.hp) ? r.pelamar.hp : (zoomContext.hp || '');
      zoomContext.email = (r.pelamar && r.pelamar.email) ? r.pelamar.email : (zoomContext.email || '');
      return;
    }

    zoomPreview.textContent = 'Gagal membuat preview pesan.';
  }

  async function openZoom(ctx){
    zoomContext = Object.assign({}, ctx);
    zoomTitle.textContent = 'Interview (Online/Offline)';
    zoomMeta.innerHTML = `<b>${esc(ctx.nama||'-')}</b>`;

    // default
    zoomMetode.value = 'online';
    zoomTanggal.value = '';
    zoomJam.value = '';
    zoomLink.value = '';
    zoomLokasi.value = '';
    zoomPreview.textContent = '—';

    setZoomModeUI();

    zoomMask.style.display = 'flex';
    zoomMask.setAttribute('aria-hidden','false');

    // preload zoom tersimpan
    try{
      const rz = await jget({action:'get_zoom', lowongan_id:selectedLowonganId, pelamar_id:ctx.pelamar_id});
      if(rz && rz.ok && rz.zoom){
        zoomMetode.value  = (rz.zoom.metode || 'online');
        zoomTanggal.value = (rz.zoom.tanggal || '');
        zoomJam.value     = (rz.zoom.jam || '');
        zoomLink.value    = (rz.zoom.link || '');
        zoomLokasi.value  = (rz.zoom.lokasi || '');
      }
    }catch(e){}

    setZoomModeUI();
    await refreshZoomPreview();
  }

  function closeZoom(){
    zoomMask.style.display = 'none';
    zoomMask.setAttribute('aria-hidden','true');
    zoomContext = null;
  }

  zoomClose.onclick = closeZoom;
  zoomCancel.onclick = closeZoom;
  zoomMask.addEventListener('click', (e)=>{ if(e.target === zoomMask) closeZoom(); });

  zoomMetode.addEventListener('change', ()=>{ setZoomModeUI(); refreshZoomPreview(); });
  zoomTanggal.addEventListener('input', ()=>{ refreshZoomPreview(); });
  zoomJam.addEventListener('input', ()=>{ refreshZoomPreview(); });
  zoomLink.addEventListener('input', ()=>{ refreshZoomPreview(); });
  zoomLokasi.addEventListener('input', ()=>{ refreshZoomPreview(); });

  zoomSave.onclick = async () => {
    if(!zoomContext) return;
    const metode = (zoomMetode.value || 'online').trim();
    const link = (zoomLink.value || '').trim();
    const lokasi = (zoomLokasi.value || '').trim();

    if(metode === 'online' && !link){
      alert('Metode Online dipilih, tapi Link Zoom masih kosong. Silakan paste link Zoom asli.');
      return;
    }

    const r = await jpost({
      action:'save_zoom',
      lowongan_id:selectedLowonganId,
      pelamar_id:zoomContext.pelamar_id,
      metode,
      tanggal:(zoomTanggal.value||'').trim(),
      jam:(zoomJam.value||'').trim(),
      link,
      lokasi
    });
    if(!r.ok){ alert(r.error || 'Gagal simpan data interview.'); return; }
    alert('Data interview tersimpan.');
    await refreshAllCountsAndRender(false);
  };

  zoomSend.onclick = async () => {
    if(!zoomContext) return;
    const metode = (zoomMetode.value || 'online').trim();
    const link = (zoomLink.value || '').trim();
    const lokasi = (zoomLokasi.value || '').trim();

    if(metode === 'online' && !link){
      alert('Metode Online dipilih, tapi Link Zoom masih kosong. Silakan paste link Zoom asli dulu.');
      return;
    }

    // Simpan dulu
    const rs = await jpost({
      action:'save_zoom',
      lowongan_id:selectedLowonganId,
      pelamar_id:zoomContext.pelamar_id,
      metode,
      tanggal:(zoomTanggal.value||'').trim(),
      jam:(zoomJam.value||'').trim(),
      link,
      lokasi
    });
    if(!rs.ok){ alert(rs.error || 'Gagal simpan data interview.'); return; }

    // Ambil message final
    const rm = await jget({
      action:"zoom_meta",
      lowongan_id: selectedLowonganId,
      pelamar_id: zoomContext.pelamar_id,
      metode,
      tanggal:(zoomTanggal.value||'').trim(),
      jam:(zoomJam.value||'').trim(),
      link,
      lokasi
    });
    if(!(rm && rm.ok)){
      alert((rm && rm.error) ? rm.error : 'Gagal membuat pesan.');
      return;
    }

    const msg = rm.message || '';
    const hpRaw = (rm.pelamar && rm.pelamar.hp) ? rm.pelamar.hp : (zoomContext.hp||'');
    const email = (rm.pelamar && rm.pelamar.email) ? rm.pelamar.email : (zoomContext.email||'');

    // PRIORITAS: WA kalau ada HP, kalau tidak: email
    const hp = normalizeHP(hpRaw);
    if(hp){
      window.open(waLink(hp, msg), '_blank');
    }else if(email){
      window.open(mailtoLink(email, 'Undangan Interview PT Wiraswasta Gemilang Indonesia', msg), '_blank');
    }else{
      alert('Nomor HP dan Email kandidat kosong. Tidak bisa kirim.');
      return;
    }

    closeZoom();
  };
function statusText(aksi){
    if(!aksi || !aksi.status) return '<span class="muted">—</span>';
    const dt = aksi.date_human || '';
    return `${esc(aksi.status)}${dt ? ' ' + esc(dt) : ''}`;
  }

  function stageLabels(stage){
    if(stage === "Onboarding"){
      return { okText:"Diterima", noText:"Tidak Diterima", okVal:"terima", noVal:"ditolak" };
    }
    return { okText:"Lolos", noText:"Tidak Lolos", okVal:"lolos", noVal:"tidak_lolos" };
  }

  function canUpload(stage){
    return (stage === "Interview HRD & User" || stage === "Psikotest" || stage === "MCU");
  }
  function uploadLabel(stage){
    if(stage === "Interview HRD & User") return "Upload Interview";
    if(stage === "Psikotest") return "Upload Psikotest";
    if(stage === "MCU") return "Upload MCU";
    return "Upload";
  }

  function setHeadForTab(){
    panelTitle.textContent = (activeTab === "Favorit") ? "Data Favorit" : (activeTab === "Interview HRD & User" ? "Interview" : activeTab);
    btnClear.style.display = (activeTab === "Favorit" || selectedLowonganId === "all") ? "none" : "inline-flex";
    btnDownload.style.display = "none";
  }

  function renderHeadFavorit(){
    theadMain.innerHTML = `
      <tr>
        <th style="width:70px">No</th>
        <th style="min-width:260px">Nama</th>
        <th style="width:140px">Tools</th>
        <th style="width:220px">Aksi</th>
        <th style="width:120px">Hapus</th>
      </tr>
    `;
  }

  function renderHeadStage(stage){
    if(stage === "Initiate Call"){
      theadMain.innerHTML = `
        <tr>
          <th style="width:70px">No</th>
          <th style="min-width:260px">Nama</th>
          <th style="width:230px">Tools</th>
          <th style="width:200px">Aksi</th>
          <th style="width:320px">Status</th>
          <th style="width:120px">Hapus</th>
        </tr>
      `;
      return;
    }

    theadMain.innerHTML = `
      <tr>
        <th style="width:70px">No</th>
        <th style="min-width:260px">Nama</th>
        <th style="width:210px">Tools</th>
        <th style="width:200px">Aksi</th>
        <th style="width:320px">Status</th>
        <th style="width:260px">Hasil & Upload</th>
        <th style="width:120px">Hapus</th>
      </tr>
    `;
  }

  async function renderFavorit(){
    renderHeadFavorit();
    tbodyMain.innerHTML = "";
    if(!selectedLowonganId || selectedLowonganId === "all"){
      showEmpty("Pilih posisi dulu.");
      return;
    }
    hideEmpty();

    const r = await jget({action:"get_favorit", lowongan_id:selectedLowonganId});
    if(!r.ok){
      showEmpty(r.error || "Gagal load favorit.");
      return;
    }

    const items = (r.items || []).filter(x => detectMatchName(x.nama));
    if(items.length === 0){
      showEmpty("Tidak ada data favorit.");
      return;
    }

    tbodyMain.innerHTML = items.map((p, i) => {
      const ak = p.aksi || null;

      const aksiCell = (ak && ak.status)
        ? `<div class="sf-picked">Sudah dipilih</div>`
        : `
          <div class="sf-actions">
            <div class="sf-icon ok" data-fav="${p.id}" data-val="lolos" title="Lolos & Masuk Initiate Call">✅</div>
            <div class="sf-icon no" data-fav="${p.id}" data-val="tidak_lolos" title="Tidak Lolos">❌</div>
          </div>
        `;

      return `
        <tr data-pid="${p.id}" data-hp="${esc(p.hp || '')}" data-email="${esc(p.email || '')}">
          <td class="sf-no">${i+1}</td>
          <td title="${esc(p.nama||'')}"><div class="sf-name">${esc(p.nama || "-")}</div></td>

          <td>
            <div class="sf-tools">
              <button class="btn-sm btn-detail" data-fav-detail="${p.id}" data-nama="${esc(p.nama||"")}">Detail</button>
            </div>
          </td>

          <td class="sf-aksi-td">${aksiCell}</td>

          <td>
            <div class="sf-del">
              <button class="delbtn" data-del-fav="${p.id}">Hapus</button>
            </div>
          </td>
        </tr>
      `;
    }).join("");

    tbodyMain.querySelectorAll("[data-fav-detail]").forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        openDetail(btn.getAttribute("data-fav-detail"), btn.getAttribute("data-nama") || "", "cv");
      };
    });

    tbodyMain.querySelectorAll("[data-fav]").forEach(div => {
      div.onclick = async (e) => {
        e.preventDefault();
        const pid = div.getAttribute("data-fav");
        const val = div.getAttribute("data-val");
        const rr = await jpost({
          action:"fav_decision",
          lowongan_id:selectedLowonganId,
          pelamar_id:pid,
          value:val
        });
        if(!rr.ok){ alert(rr.error || "Gagal"); return; }

        if(val === "lolos"){
          activeTab = "Initiate Call";
        }
        await refreshAllCountsAndRender();
      };
    });

    tbodyMain.querySelectorAll("[data-del-fav]").forEach(btn => {
      btn.onclick = async (e) => {
        e.preventDefault();
        const pid = btn.getAttribute("data-del-fav");
        const yes = confirm("Hapus kandidat ini dari Data Favorit?");
        if(!yes) return;

        const rr = await jpost({
          action:"remove_favorit",
          lowongan_id:selectedLowonganId,
          pelamar_id:pid
        });
        if(!rr.ok){ alert(rr.error || "Gagal hapus favorit."); return; }

        await refreshAllCountsAndRender();
      };
    });
  }

  async function renderStage(stage){
    renderHeadStage(stage);
    tbodyMain.innerHTML = "";
    if(!selectedLowonganId || selectedLowonganId === "all"){
      showEmpty("Pilih posisi dulu.");
      return;
    }
    hideEmpty();

    const r = await jget({action:"get_board", lowongan_id:selectedLowonganId});
    if(!r.ok){
      showEmpty(r.error || "Gagal load board.");
      return;
    }

    const items = (r.board && r.board[stage]) ? r.board[stage] : [];
    const list = (items || []).filter(x => detectMatchName(x.nama));
    if(list.length === 0){
      showEmpty("Kosong.");
      return;
    }

    const lab = stageLabels(stage);
    const allowUpload = canUpload(stage);
    const isInitiate = (stage === "Initiate Call");
    const isInterview = (stage === "Interview HRD & User");

    let zoomMap = {};
    if(isInterview){
      try{
        const rz = await jget({action:'get_zoom_map', lowongan_id:selectedLowonganId});
        if(rz && rz.ok && rz.map) zoomMap = rz.map;
      }catch(e){}
    }

    tbodyMain.innerHTML = list.map((p, i) => {
      const aksiSudah = (p.aksi && p.aksi.status);
      const baseAksi = aksiSudah
        ? `<div class="sf-picked">Sudah dipilih</div>`
        : `
          <div class="sf-actions">
            <div class="sf-icon ok" data-aksi="${lab.okVal}" data-id="${p.id}" title="${esc(lab.okText)}">✅</div>
            <div class="sf-icon no" data-aksi="${lab.noVal}" data-id="${p.id}" title="${esc(lab.noText)}">❌</div>
          </div>
        `;

      // ✅ Zoom hanya untuk Interview HRD & User (muncul di bawah X & Ceklis)
      const aksiCell = isInterview
        ? `<div class="zoomStack">${baseAksi}<button class="zoomBtn" data-zoom="${p.id}" data-nama="${esc(p.nama||"")}">${zoomMap[String(p.id)] ? 'Zoom' : 'Zoom'}</button></div>`
        : baseAksi;

      const up = p.upload;
      const link = up && up.file ? `<a class="fileLink" href="${esc(up.file)}" target="_blank">⬇ ${esc(up.name||"Download")}</a>` : '';
      const upBtn = allowUpload ? `<button class="uploadBtn" data-upload="${p.id}">${esc(uploadLabel(stage))}</button>` : '';

      const toolsHtml = isInitiate
        ? `
          <button class="btn-sm btn-detail" data-detail="${p.id}" data-nama="${esc(p.nama||"")}">Detail</button>
          <button class="btn-sm btn-note" data-note="${p.id}" data-nama="${esc(p.nama||"")}">Catatan</button>
        `
        : `
          <button class="btn-sm btn-detail" data-detail="${p.id}" data-nama="${esc(p.nama||"")}">Detail</button>
        `;

      if(isInitiate){
        return `
          <tr data-pid="${p.id}" data-hp="${esc(p.hp || '')}" data-email="${esc(p.email || '')}">
            <td class="sf-no">${i+1}</td>
            <td title="${esc(p.nama||'')}"><div class="sf-name">${esc(p.nama || "-")}</div></td>
            <td><div class="sf-tools">${toolsHtml}</div></td>
            <td class="sf-aksi-td">${aksiCell}</td>
            <td class="sf-status">${statusText(p.aksi)}</td>
            <td><div class="sf-del"><button class="delbtn" data-del="${p.id}">Hapus</button></div></td>
          </tr>
        `;
      }

      return `
        <tr data-pid="${p.id}" data-hp="${esc(p.hp || '')}" data-email="${esc(p.email || '')}">
          <td class="sf-no">${i+1}</td>
          <td title="${esc(p.nama||'')}"><div class="sf-name">${esc(p.nama || "-")}</div></td>
          <td><div class="sf-tools">${toolsHtml}</div></td>
          <td class="sf-aksi-td">${aksiCell}</td>
          <td class="sf-status">${statusText(p.aksi)}</td>
          <td>
            <div class="sf-upload">
              ${upBtn}
              ${link || '<span class="muted" style="font-weight:900;opacity:.7">—</span>'}
            </div>
          </td>
          <td><div class="sf-del"><button class="delbtn" data-del="${p.id}">Hapus</button></div></td>
        </tr>
      `;
    }).join("");

    tbodyMain.querySelectorAll("[data-detail]").forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        openDetail(btn.getAttribute("data-detail"), btn.getAttribute("data-nama") || "", "");
      };
    });

    if(stage === "Initiate Call"){
      tbodyMain.querySelectorAll("[data-note]").forEach(btn => {
        btn.onclick = (e) => {
          e.preventDefault();
          openNote({
            pelamar_id: btn.getAttribute("data-note"),
            nama: btn.getAttribute("data-nama"),
          });
        };
      });
    }

    // ✅ Zoom button khusus Interview
    if(stage === "Interview HRD & User"){
      tbodyMain.querySelectorAll("[data-zoom]").forEach(btn => {
        btn.onclick = (e) => {
          e.preventDefault();
          openZoom({
            pelamar_id: btn.getAttribute("data-zoom"),
            nama: btn.getAttribute("data-nama") || "",
          });
        };
      });
    }

    tbodyMain.querySelectorAll("[data-aksi]").forEach(div => {
      div.onclick = async (e) => {
        e.preventDefault();
        const pid = div.getAttribute("data-id");
        const value = div.getAttribute("data-aksi");
        const rr = await jpost({
          action:"set_aksi",
          lowongan_id:selectedLowonganId,
          pelamar_id:pid,
          stage: stage,
          value
        });
        if(!rr.ok){ alert(rr.error || "Gagal set aksi."); return; }
        await refreshAllCountsAndRender(false);
      };
    });

    tbodyMain.querySelectorAll("[data-del]").forEach(btn => {
      btn.onclick = async (e) => {
        e.preventDefault();
        const pid = btn.getAttribute("data-del");
        const yes = confirm("Hapus kandidat ini dari tahap?");
        if(!yes) return;
        const rr = await jpost({
          action:"remove_from_stage",
          lowongan_id:selectedLowonganId,
          pelamar_id:pid,
          stage: stage
        });
        if(!rr.ok){ alert(rr.error || "Gagal hapus."); return; }
        await refreshAllCountsAndRender(false);
      };
    });

    tbodyMain.querySelectorAll("[data-upload]").forEach(btn => {
      btn.onclick = async (e) => {
        e.preventDefault();
        const pid = btn.getAttribute("data-upload");
        const inp = document.createElement("input");
        inp.type = "file";
        inp.accept = ".xls,.xlsx,.csv";
        inp.onchange = async () => {
          const file = inp.files && inp.files[0];
          if(!file) return;
          const rr = await uploadExcel({
            lowongan_id: selectedLowonganId,
            pelamar_id: pid,
            stage: stage,
            file
          });
          if(!rr.ok){ alert(rr.error || "Upload gagal"); return; }
          await refreshAllCountsAndRender(false);
        };
        inp.click();
      };
    });
  }

  async function renderCurrent(){
    setHeadForTab();
    await refreshAllCountsAndRender(false);
  }

  async function refreshAllCountsAndRender(){
    if(!selectedLowonganId || selectedLowonganId === "all"){
      renderTabs({}, 0);
      tbodyMain.innerHTML = "";
      theadMain.innerHTML = "";
      showEmpty(!selectedLowonganId ? "Pilih posisi dulu." : "Mode Semua Posisi: untuk lihat board, pilih posisi tertentu. Untuk laporan, klik Download Excel.");
      return;
    }

    const fav = await jget({action:"get_favorit", lowongan_id:selectedLowonganId});
    const favItems = fav.ok ? (fav.items||[]) : [];
    const favCount = favItems.filter(x => detectMatchName(x.nama)).length;

    const b = await jget({action:"get_board", lowongan_id:selectedLowonganId});
    const counts = (b.ok && b.counts) ? b.counts : {};

    renderTabs(counts, favCount);

    if(activeTab === "Favorit"){
      await renderFavorit();
    }else{
      await renderStage(activeTab);
    }
    setHeadForTab();
  }

  let t = null;
  cariInput.addEventListener("input", ()=>{
    clearTimeout(t);
    t = setTimeout(()=> {
      if(selectedLowonganId === "all") return;
      refreshAllCountsAndRender(false);
    }, 180);
  });

  (async function init(){
    await loadPositions();
    renderTabs({}, 0);
    theadMain.innerHTML = "";
    tbodyMain.innerHTML = "";
    showEmpty("Pilih posisi dulu.");
    setHeadForTab();
  })();


  /* ============================================================
     ✅ BLOK CHAT WA (FIX FINAL)
  ============================================================ */

  function normalizeHP(hp){
    if(!hp) return '';
    let n = String(hp).replace(/[^0-9]/g,'');
    if(!n) return '';
    if(n.startsWith('0')) n = '62' + n.slice(1);
    if(!n.startsWith('62')) n = '62' + n;
    return n;
  }
  function waLink(hp, text){
    return `https://wa.me/${hp}?text=${encodeURIComponent(text || '')}`;
  }
  function selectedPosisiText(){
    const t = (posisiSelect?.options?.[posisiSelect.selectedIndex]?.textContent || '').trim();
    return t;
  }
  function splitPosisiLokasi(text){
    if(!text) return {posisi:'', lokasi:''};
    if(text.includes('—')){
      const parts = text.split('—');
      return {posisi:(parts[0]||'').trim(), lokasi:(parts[1]||'').trim()};
    }
    return {posisi:text.trim(), lokasi:''};
  }

  // ✅ FIX: selalu return string, tidak akan "undefined"
  function buildChatMessage({nama, posisi, lokasi, stage, value}){
  nama   = (nama || '').trim() || 'Kandidat';
  posisi = (posisi || '').trim() || '-';
  lokasi = (lokasi || '').trim();

  const stageRaw = String(stage || '').trim();
  const stageKey = stageRaw.toUpperCase();
  const val = String(value || '').trim().toLowerCase();

  // Nama tahapan yang rapi untuk ditampilkan
  const stageHuman = (() => {
    if(stageKey === "FAVORIT") return "Seleksi Administrasi";
    if(stageKey === "INITIATE CALL") return "Initiate Call";
    if(stageKey === "INTERVIEW HRD & USER") return "Interview HRD & User";
    if(stageKey === "PSIKOTEST") return "Psikotest";
    if(stageKey === "MCU") return "Medical Check Up (MCU)";
    if(stageKey === "ONBOARDING") return "Onboarding / Orientasi & PKWT";
    return stageRaw || "Tahap Seleksi";
  })();

  const head =
`Yth. Bapak/Ibu ${nama},

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi ${posisi}${lokasi ? ' ('+lokasi+')' : ''}.

`;

  // ✅ Favorit => Seleksi Administrasi
  if(stageKey === "FAVORIT"){
    if(val === "lolos"){
      return head +
`Kami informasikan bahwa Anda DINYATAKAN LOLOS
pada tahap Seleksi Administrasi.

Tahap selanjutnya adalah:
INITIATE CALL (panggilan awal dari tim HR).

Mohon menunggu informasi jadwal selanjutnya dari tim kami,
atau silakan membalas pesan ini untuk konfirmasi ketersediaan Anda.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
    } else {
      // ✅ Tidak lolos favorit: pakai format yang kamu mau
      return head +
`Kami informasikan bahwa Anda Tidak Lolos Dalam Tahap Seleksi Administrasi

Tetap semangat dan jangan berkecil hati—setiap proses adalah pengalaman berharga
untuk pengembangan karier. Kami mendoakan Anda segera mendapatkan kesempatan terbaik,
dan kami mempersilakan Anda untuk melamar kembali pada lowongan berikutnya
yang sesuai dengan kualifikasi Anda.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
    }
  }

  // ✅ MCU lolos: sesuai format kamu
  if(stageKey === "MCU" && val === "lolos"){
    return head +
`Kami informasikan bahwa Anda DINYATAKAN LOLOS
pada tahap Medical Check Up ( MCU )

Tahap selanjutnya Onboarding / Orientasi & PKWT (TandaTangan Kontrak)

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
  }

  // ✅ Onboarding diterima
  if(stageKey === "ONBOARDING" && (val === "terima" || val === "diterima")){
    return head +
`Kami informasikan bahwa Anda DINYATAKAN DITERIMA
Pada Posisi ${posisi}${lokasi ? ' ('+lokasi+')' : ''}

Selamat Bergabung Di PT Wiraswasta Gemilang Indonesia

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
  }

  // ✅ Umum: LOLOS
  if(val === "lolos"){
    return head +
`Kami informasikan bahwa Anda DINYATAKAN LOLOS
pada tahap ${stageHuman}.

Tahap selanjutnya akan diinformasikan melalui pesan berikutnya.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
  }

  // ✅ Umum: TIDAK LOLOS (INI YANG KAMU MAU)
  return head +
`Kami informasikan bahwa Anda Tidak Lolos Dalam Tahap ${stageHuman}

Tetap semangat dan jangan berkecil hati—setiap proses adalah pengalaman berharga
untuk pengembangan karier. Kami mendoakan Anda segera mendapatkan kesempatan terbaik,
dan kami mempersilakan Anda untuk melamar kembali pada lowongan berikutnya
yang sesuai dengan kualifikasi Anda.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia`;
}


  function openWAForRow(pelamar_id, stage, value){
    const row = document.querySelector(`tr[data-pid="${pelamar_id}"]`);
    if(!row) return;

    const nama = (row.querySelector('.sf-name')?.innerText || '').trim();
    const hpRaw = row.getAttribute('data-hp') || '';
    const hp = normalizeHP(hpRaw);
    if(!hp){
      alert("Nomor HP kandidat belum ada / kosong. Pastikan kolom no_hp/telepon terisi di data pelamar.");
      return;
    }

    const posTxt = selectedPosisiText();
    const sp = splitPosisiLokasi(posTxt.replace('Semua Posisi','').trim());
    const posisi = sp.posisi || posTxt || '-';
    const lokasi = sp.lokasi || '';

    const msg = buildChatMessage({nama, posisi, lokasi, stage, value});
    window.open(waLink(hp, msg), "_blank");
  }

  // Hook: setelah berhasil set aksi, langsung buka WA
  const _jpost = jpost;
  jpost = async (data) => {
    const r = await _jpost(data);

    if(r && r.ok && data && data.action){
      // Favorit
      if(data.action === 'fav_decision'){
        openWAForRow(data.pelamar_id, 'Favorit', data.value);
      }

      // Board stage
      if(data.action === 'set_aksi'){
        const stage = data.stage;
        const valueRaw = data.value;

        // map value onboarding agar teks sesuai
        // - terima => diterima
        // - ditolak => tidak lolos
        let value = valueRaw;
        if(valueRaw === 'terima') value = 'diterima';
        if(valueRaw === 'ditolak') value = 'tidak_lolos';

        openWAForRow(data.pelamar_id, stage, value);
      }
    }

    return r;
  };

  // expose (jaga-jaga)
  window.jpost = jpost;

})();
</script>

</body>
</html>
