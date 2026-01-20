<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';
header('Content-Type: text/html; charset=utf-8');

/**
 * OPTIONAL: batasi hanya HRD/Manager kalau kamu punya session role.
 * Kalau kamu belum yakin nama key session-nya, biarin aja (jangan bikin error).
 *
 * Contoh:
 * $role = strtolower($_SESSION['role'] ?? '');
 * if(!in_array($role, ['hrd','manager'])){ die('Akses ditolak'); }
 */

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ambil nama user login (aman-aman aja kalau kosong)
$meName = $_SESSION['nama'] ?? $_SESSION['username'] ?? $_SESSION['email'] ?? 'User';
$meRole = $_SESSION['role'] ?? $_SESSION['level'] ?? 'HRD/Manager';

// list lowongan untuk dropdown attach
$listLow = [];
$qLow = mysqli_query($conn, "SELECT id, posisi FROM lowongan ORDER BY posisi ASC");
if($qLow){
  while($r = mysqli_fetch_assoc($qLow)){
    $listLow[] = $r;
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat HRD</title>
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    body{ background:#f4f7fb; }

    /* layout ngikut dashboard kamu: sidebar fixed 280px */
    .page-wrap{
      margin-left: 300px;
      padding: 22px 22px 30px;
    }
    @media(max-width:900px){
      .page-wrap{ margin-left: 0; padding: 14px; }
    }

    .top-hero{
      background: linear-gradient(135deg, #2563eb, #4f46e5);
      border-radius: 18px;
      padding: 18px 18px;
      color: #fff;
      box-shadow: 0 18px 45px rgba(37,99,235,.25);
      margin-bottom: 16px;
    }
    .top-hero h1{
      margin:0;
      font-size: 28px;
      letter-spacing: .2px;
    }
    .top-hero .sub{
      margin-top: 6px;
      opacity:.9;
      font-size: 13px;
    }

    .chat-card{
      background:#fff;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(2,6,23,.08);
      overflow:hidden;
      border: 1px solid rgba(15,23,42,.06);
    }

    .chat-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding: 14px 16px;
      background: linear-gradient(180deg, #f8fafc, #ffffff);
      border-bottom:1px solid rgba(15,23,42,.08);
    }
    .chat-header .left{
      display:flex;
      flex-direction:column;
      gap:3px;
    }
    .chat-title{
      font-weight:900;
      color:#0f172a;
      font-size:14px;
      letter-spacing:.2px;
    }
    .chat-meta{
      color:#64748b;
      font-size:12px;
    }

    .chat-header .right{
      display:flex;
      gap:10px;
      align-items:center;
    }

    .btn{
      border: 0;
      cursor:pointer;
      border-radius: 12px;
      padding: 10px 12px;
      font-weight:800;
      font-size: 13px;
      display:flex;
      align-items:center;
      gap:10px;
      transition: .15s ease;
      user-select:none;
      white-space:nowrap;
    }
    .btn:active{ transform: translateY(1px); }

    .btn-attach{
      background: rgba(37,99,235,.10);
      color:#1d4ed8;
    }
    .btn-attach:hover{
      background: rgba(37,99,235,.14);
    }

    .btn-refresh{
      background: rgba(15,23,42,.06);
      color:#0f172a;
    }
    .btn-refresh:hover{ background: rgba(15,23,42,.09); }

    .chat-body{
      height: 58vh;
      min-height: 420px;
      max-height: 760px;
      overflow:auto;
      padding: 14px 16px;
      background: #fbfdff;
    }

    .msg{
      display:flex;
      margin: 10px 0;
      gap:10px;
      align-items:flex-end;
    }
    .msg.me{ justify-content:flex-end; }
    .bubble{
      max-width: 76%;
      background:#fff;
      border:1px solid rgba(15,23,42,.08);
      border-radius: 16px;
      padding: 10px 12px;
      box-shadow: 0 8px 22px rgba(2,6,23,.06);
    }
    .msg.me .bubble{
      background: linear-gradient(135deg, #2563eb, #4f46e5);
      color:#fff;
      border:0;
    }

    .bubble .who{
      display:flex;
      gap:8px;
      align-items:center;
      font-weight:900;
      font-size: 12px;
      margin-bottom: 6px;
      opacity:.95;
    }
    .chip{
      font-size: 11px;
      font-weight:900;
      padding: 3px 8px;
      border-radius: 999px;
      background: rgba(15,23,42,.06);
      color:#0f172a;
    }
    .msg.me .chip{
      background: rgba(255,255,255,.22);
      color:#fff;
    }
    .bubble .text{
      white-space: pre-wrap;
      word-break: break-word;
      font-size: 13px;
      line-height: 1.45;
    }
    .bubble .refs{
      margin-top: 8px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }
    .ref-pill{
      display:inline-flex;
      gap:8px;
      align-items:center;
      font-size: 12px;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(2,6,23,.04);
      border:1px dashed rgba(2,6,23,.18);
      color:#0f172a;
    }
    .msg.me .ref-pill{
      background: rgba(255,255,255,.16);
      border: 1px dashed rgba(255,255,255,.35);
      color:#fff;
    }
    .bubble .time{
      margin-top: 6px;
      font-size: 11px;
      opacity:.7;
    }

    .chat-input{
      padding: 12px 12px;
      border-top:1px solid rgba(15,23,42,.08);
      display:flex;
      gap:10px;
      background:#fff;
    }
    .chat-input textarea{
      flex:1;
      min-height: 44px;
      max-height: 140px;
      resize: vertical;
      border-radius: 14px;
      border:1px solid rgba(15,23,42,.12);
      padding: 10px 12px;
      font-size: 13px;
      outline:none;
    }
    .chat-input textarea:focus{
      border-color: rgba(37,99,235,.45);
      box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }
    .btn-send{
      background: linear-gradient(135deg, #1d4ed8, #2563eb);
      color:#fff;
      padding: 12px 14px;
    }
    .btn-send:hover{ filter: brightness(1.02); }

    /* modal attach */
    .modal-backdrop{
      position: fixed;
      inset:0;
      background: rgba(2,6,23,.55);
      display:none;
      align-items:center;
      justify-content:center;
      z-index: 99999;
      padding: 18px;
    }
    .modal{
      width: 100%;
      max-width: 560px;
      background:#fff;
      border-radius: 18px;
      box-shadow: 0 30px 80px rgba(2,6,23,.35);
      overflow:hidden;
      border: 1px solid rgba(255,255,255,.12);
    }
    .modal-head{
      padding: 14px 16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      border-bottom:1px solid rgba(15,23,42,.08);
      background: linear-gradient(180deg, #f8fafc, #ffffff);
    }
    .modal-title{
      font-weight: 900;
      color:#0f172a;
      font-size: 14px;
    }
    .modal-close{
      border:0;
      cursor:pointer;
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: rgba(15,23,42,.06);
      font-weight: 900;
    }
    .modal-body{
      padding: 14px 16px 16px;
    }
    .row{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom: 12px;
    }
    .field{
      flex:1;
      min-width: 220px;
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .field label{
      font-size: 12px;
      color:#475569;
      font-weight: 800;
    }
    .field select, .field input{
      border:1px solid rgba(15,23,42,.12);
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 13px;
      outline:none;
      background:#fff;
    }
    .field select:focus, .field input:focus{
      border-color: rgba(37,99,235,.45);
      box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }

    .mini{
      font-size: 12px;
      color:#64748b;
      margin-top: 6px;
    }

    .modal-actions{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding-top: 4px;
    }
    .btn-secondary{
      background: rgba(15,23,42,.06);
      color:#0f172a;
    }
    .btn-primary{
      background: linear-gradient(135deg, #2563eb, #4f46e5);
      color:#fff;
    }
  </style>
</head>

<body>

<?php include __DIR__ . "/sidebar.php"; ?>

<div class="page-wrap">
  <div class="top-hero">
    <h1>Chat HRD</h1>
    <div class="sub">
      Selamat datang, <b><?= e($meName) ?></b> • Komunikasi HRD & Manager (tanpa database — tersimpan JSON)
    </div>
  </div>

  <div class="chat-card">
    <div class="chat-header">
      <div class="left">
        <div class="chat-title">Room: HRD & Manager</div>
        <div class="chat-meta" id="chatMeta">Memuat chat...</div>
      </div>
      <div class="right">
        <button class="btn btn-attach" id="btnAttach" type="button" title="Tandai pelamar & lowongan (vector)">
          <!-- simple vector icon -->
          <span style="font-size:18px;line-height:0;">📎</span>
          <span>Vector</span>
        </button>
        <button class="btn btn-refresh" id="btnRefresh" type="button">Refresh</button>
      </div>
    </div>

    <div class="chat-body" id="chatBody"></div>

    <div class="chat-input">
      <textarea id="msgText" placeholder="Tulis chat... (Enter untuk kirim, Shift+Enter untuk baris baru)"></textarea>
      <button class="btn btn-send" id="btnSend" type="button">Kirim</button>
    </div>
  </div>
</div>

<!-- Modal Attach / Vector -->
<div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <div class="modal-title">Tandai Pelamar & Lowongan</div>
      <button class="modal-close" id="modalClose" type="button">✕</button>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="field">
          <label>Pilih Lowongan</label>
          <select id="pickLowongan">
            <option value="">— pilih lowongan —</option>
            <?php foreach($listLow as $l): ?>
              <option value="<?= (int)$l['id'] ?>"><?= e($l['posisi']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="mini">Setelah pilih lowongan, daftar pelamar akan muncul.</div>
        </div>

        <div class="field">
          <label>Cari Nama Pelamar (opsional)</label>
          <input id="searchPelamar" type="text" placeholder="Ketik nama...">
          <div class="mini">Supaya cepat kalau pelamarnya banyak.</div>
        </div>
      </div>

      <div class="row">
        <div class="field" style="flex:1; min-width:100%;">
          <label>Pilih Pelamar</label>
          <select id="pickPelamar">
            <option value="">— pilih pelamar —</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="field" style="flex:1; min-width:100%;">
          <label>Template Chat</label>
          <select id="pickTemplate">
            <option value="rekomendasi">Rekomendasi pelamar (bagus untuk next step)</option>
            <option value="jadwal">Reminder jadwal interview</option>
            <option value="followup">Follow-up screening (butuh review)</option>
          </select>
          <div class="mini">Nanti otomatis bikin kalimat, kamu masih bisa edit sebelum kirim.</div>
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn btn-secondary" id="btnCancelAttach" type="button">Batal</button>
        <button class="btn btn-primary" id="btnInsertAttach" type="button">Masukkan ke Chat</button>
      </div>
    </div>
  </div>
</div>

<script>
  const CHAT_API = "chat_api.php";

  const chatBody = document.getElementById('chatBody');
  const chatMeta = document.getElementById('chatMeta');
  const msgText  = document.getElementById('msgText');

  const btnSend    = document.getElementById('btnSend');
  const btnRefresh = document.getElementById('btnRefresh');

  // modal
  const modalBackdrop = document.getElementById('modalBackdrop');
  const btnAttach = document.getElementById('btnAttach');
  const modalClose = document.getElementById('modalClose');
  const btnCancelAttach = document.getElementById('btnCancelAttach');
  const btnInsertAttach = document.getElementById('btnInsertAttach');

  const pickLowongan = document.getElementById('pickLowongan');
  const pickPelamar  = document.getElementById('pickPelamar');
  const searchPelamar = document.getElementById('searchPelamar');
  const pickTemplate = document.getElementById('pickTemplate');

  let lastTs = 0;
  let pollTimer = null;
  let lastRenderedCount = 0;

  function escapeHtml(str){
    return (str ?? '').toString()
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function fmtTime(ts){
    try{
      const d = new Date(ts*1000);
      const pad = n => String(n).padStart(2,'0');
      return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()+' '+pad(d.getHours())+':'+pad(d.getMinutes());
    }catch(e){
      return '';
    }
  }

  function scrollToBottom(force=false){
    if(force){
      chatBody.scrollTop = chatBody.scrollHeight;
      return;
    }
    // auto scroll hanya kalau user memang di bawah
    const nearBottom = (chatBody.scrollTop + chatBody.clientHeight) >= (chatBody.scrollHeight - 120);
    if(nearBottom) chatBody.scrollTop = chatBody.scrollHeight;
  }

  function renderMessages(messages, me){
    if(!Array.isArray(messages)) messages = [];
    let html = '';
    for(const m of messages){
      const isMe = (m.user && me && (m.user.id && me.id) && (m.user.id == me.id)) || (m.user && me && m.user.name && me.name && m.user.name === me.name);
      const cls = isMe ? 'msg me' : 'msg';

      let refsHtml = '';
      if(m.ref && (m.ref.pelamar_nama || m.ref.lowongan_posisi)){
        const p = m.ref.pelamar_nama ? `Pelamar: <b>${escapeHtml(m.ref.pelamar_nama)}</b>${m.ref.pelamar_id ? ' (#'+escapeHtml(m.ref.pelamar_id)+')' : ''}` : '';
        const l = m.ref.lowongan_posisi ? `Lowongan: <b>${escapeHtml(m.ref.lowongan_posisi)}</b>${m.ref.lowongan_id ? ' (#'+escapeHtml(m.ref.lowongan_id)+')' : ''}` : '';
        refsHtml = `
          <div class="refs">
            ${p ? `<span class="ref-pill">👤 ${p}</span>` : ``}
            ${l ? `<span class="ref-pill">📌 ${l}</span>` : ``}
          </div>
        `;
      }

      const who = `
        <div class="who">
          <span>${escapeHtml(m.user?.name || 'User')}</span>
          <span class="chip">${escapeHtml(m.user?.role || '')}</span>
        </div>
      `;

      html += `
        <div class="${cls}">
          <div class="bubble">
            ${who}
            <div class="text">${escapeHtml(m.text || '')}</div>
            ${refsHtml}
            <div class="time">${fmtTime(m.ts || 0)}</div>
          </div>
        </div>
      `;
    }

    chatBody.innerHTML = html || `<div style="color:#64748b;font-weight:700;">Belum ada chat. Mulai ngobrol dulu…</div>`;
    chatMeta.textContent = `Total pesan: ${messages.length} • Update: ${new Date().toLocaleTimeString()}`;

    // update lastTs
    if(messages.length){
      lastTs = Math.max(...messages.map(x => x.ts || 0));
    }

    // auto scroll
    if(messages.length !== lastRenderedCount){
      // kalau nambah pesan, scroll (halus) kalau user near bottom
      scrollToBottom(false);
      lastRenderedCount = messages.length;
    }
  }

  async function fetchMessages(){
    const res = await fetch(CHAT_API + "?action=list&after=" + encodeURIComponent(lastTs), { credentials:'same-origin' });
    const data = await res.json();
    if(data && data.ok){
      renderMessages(data.messages, data.me);
    }
  }

  async function sendMessage(payload){
    const form = new FormData();
    form.append('action','send');
    form.append('text', payload.text || '');
    if(payload.ref){
      form.append('ref', JSON.stringify(payload.ref));
    }
    const res = await fetch(CHAT_API, { method:'POST', body: form, credentials:'same-origin' });
    const data = await res.json();
    if(!data || !data.ok){
      alert((data && data.error) ? data.error : "Gagal kirim chat.");
      return false;
    }
    msgText.value = '';
    await fetchMessages();
    scrollToBottom(true);
    return true;
  }

  function startPolling(){
    if(pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
      fetchMessages().catch(()=>{});
    }, 3000);
  }

  // send handlers
  btnSend.addEventListener('click', () => {
    const text = (msgText.value || '').trim();
    if(!text) return;
    sendMessage({ text }).catch(()=>{});
  });

  btnRefresh.addEventListener('click', () => {
    fetchMessages().catch(()=>{});
  });

  msgText.addEventListener('keydown', (e) => {
    if(e.key === 'Enter' && !e.shiftKey){
      e.preventDefault();
      btnSend.click();
    }
  });

  // modal open/close
  function openModal(){
    modalBackdrop.style.display = 'flex';
    modalBackdrop.setAttribute('aria-hidden','false');
    searchPelamar.value = '';
    pickPelamar.innerHTML = `<option value="">— pilih pelamar —</option>`;
  }
  function closeModal(){
    modalBackdrop.style.display = 'none';
    modalBackdrop.setAttribute('aria-hidden','true');
  }

  btnAttach.addEventListener('click', openModal);
  modalClose.addEventListener('click', closeModal);
  btnCancelAttach.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', (e) => {
    if(e.target === modalBackdrop) closeModal();
  });

  // load pelamar by lowongan
  async function loadPelamar(){
    const lowId = pickLowongan.value || '';
    pickPelamar.innerHTML = `<option value="">Memuat...</option>`;
    const q = encodeURIComponent(searchPelamar.value || '');
    const res = await fetch(CHAT_API + `?action=pelamar&lowongan_id=${encodeURIComponent(lowId)}&q=${q}`, { credentials:'same-origin' });
    const data = await res.json();
    if(!data || !data.ok){
      pickPelamar.innerHTML = `<option value="">— gagal load —</option>`;
      return;
    }
    const items = data.items || [];
    let opt = `<option value="">— pilih pelamar —</option>`;
    for(const it of items){
      opt += `<option value="${escapeHtml(it.id)}" data-nama="${escapeHtml(it.nama)}">${escapeHtml(it.nama)}${it.kota ? ' • '+escapeHtml(it.kota) : ''}</option>`;
    }
    pickPelamar.innerHTML = opt;
  }

  let pelamarDebounce = null;
  pickLowongan.addEventListener('change', () => {
    loadPelamar().catch(()=>{});
  });
  searchPelamar.addEventListener('input', () => {
    clearTimeout(pelamarDebounce);
    pelamarDebounce = setTimeout(() => loadPelamar().catch(()=>{}), 350);
  });

  // insert template to textarea
  btnInsertAttach.addEventListener('click', async () => {
    const lowId = pickLowongan.value || '';
    const pelId = pickPelamar.value || '';
    if(!lowId){
      alert("Pilih lowongan dulu.");
      return;
    }
    if(!pelId){
      alert("Pilih pelamar dulu.");
      return;
    }

    // ambil detail lowongan + pelamar dari api (biar akurat)
    const res = await fetch(CHAT_API + `?action=detail&lowongan_id=${encodeURIComponent(lowId)}&pelamar_id=${encodeURIComponent(pelId)}`, { credentials:'same-origin' });
    const data = await res.json();
    if(!data || !data.ok){
      alert("Gagal ambil detail.");
      return;
    }

    const ref = data.ref || {};
    const tpl = pickTemplate.value || 'rekomendasi';

    let textTpl = '';
    if(tpl === 'rekomendasi'){
      textTpl =
`Saya ada rekomendasi nih:
Pelamar: ${ref.pelamar_nama}
Lowongan: ${ref.lowongan_posisi}

Menurut saya kandidat ini bagus untuk lanjut ke tahap berikutnya. Mohon review ya 🙏`;
    }else if(tpl === 'jadwal'){
      textTpl =
`Reminder jadwal interview:
Pelamar: ${ref.pelamar_nama}
Lowongan: ${ref.lowongan_posisi}

Tolong pastikan jadwal & link meetingnya sudah siap ya.`;
    }else{
      textTpl =
`Butuh follow-up screening:
Pelamar: ${ref.pelamar_nama}
Lowongan: ${ref.lowongan_posisi}

Mohon bantu review CV/hasil screening kandidat ini ya.`;
    }

    // masukin ke textarea (bisa diedit)
    msgText.value = textTpl;
    closeModal();

    // kalau kamu mau LANGSUNG KIRIM saat pilih (tanpa edit), uncomment:
    // await sendMessage({ text: textTpl, ref });

  });

  // init
  (async function init(){
    await fetchMessages().catch(()=>{});
    scrollToBottom(true);
    startPolling();
  })();
</script>

</body>
</html>
