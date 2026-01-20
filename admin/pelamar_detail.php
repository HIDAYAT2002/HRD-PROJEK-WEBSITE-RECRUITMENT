<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

header('Content-Type: text/html; charset=utf-8');

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
  echo '<div class="modal-error">ID pelamar tidak valid.</div>';
  exit;
}

// ambil data pelamar + posisi + requirement lowongan
$q = mysqli_query($conn, "
  SELECT p.*, l.posisi, l.kota AS low_kota, l.pekerjaan, l.kriteria
  FROM pelamar p
  JOIN lowongan l ON p.lowongan_id = l.id
  WHERE p.id = $id
  LIMIT 1
");
$p = $q ? mysqli_fetch_assoc($q) : null;
if(!$p){
  echo '<div class="modal-error">Data pelamar tidak ditemukan.</div>';
  exit;
}

$nama   = $p['nama'] ?? '-';
$email  = $p['email'] ?? '-';
$telp   = $p['telepon'] ?? '-';
$kota   = $p['kota'] ?? '-';
$posisi = $p['posisi'] ?? '-';

$lowKota    = $p['low_kota'] ?? '';
$jobDesk    = $p['pekerjaan'] ?? '';
$kriteria   = $p['kriteria'] ?? '';

$pendidikanFull = trim((string)($p['pendidikan'] ?? ''));
$jurusan = trim((string)($p['jurusan'] ?? ''));
if($jurusan !== '') $pendidikanFull .= ($pendidikanFull ? ' - ' : '') . $jurusan;

$tgl_lahir = $p['tgl_lahir'] ?? '';
$tglLahirView = '-';
$umurView = '-';
if(!empty($tgl_lahir) && $tgl_lahir !== '0000-00-00'){
  $tglLahirView = date('d M Y', strtotime($tgl_lahir));
  try{
    $birthDate = new DateTime($tgl_lahir);
    $today = new DateTime();
    $umurView = $today->diff($birthDate)->y . ' Tahun';
  } catch(Exception $e){
    $umurView = '-';
  }
}

$cvRaw = trim((string)($p['cv'] ?? ''));
$cvUrl = '';
$cvFound = false;

if($cvRaw !== ''){
  if(preg_match('~^https?://~i', $cvRaw)){
    $cvUrl = $cvRaw;
  }else{
    $cvUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'ptwgi.com') . "/career/uploads/" . ltrim($cvRaw,'/');
  }
  $cvFound = true;
}

// PROXY URL (kunci biar inline + ga 204)
$cvProxy = $cvFound ? ("/career/admin/cv_proxy.php?u=" . urlencode($cvUrl)) : '';
?>
<style>
  .pd-wrap{
    display:grid;
    grid-template-columns: 520px minmax(340px, 520px);
    gap:14px;
    align-items:start;
    justify-content:start;
  }
  @media (max-width: 1200px){
    .pd-wrap{ grid-template-columns: 480px minmax(320px, 480px); }
  }
  @media (max-width: 980px){
    .pd-wrap{ grid-template-columns: 1fr; }
  }

  .pd-left{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:14px;
  }
  .pd-row{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:10px;
  }
  .pd-box{
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:10px 12px;
    background:#f8fafc;
  }
  .pd-box b{ display:block; font-size:12px; color:#64748b; margin-bottom:4px; }
  .pd-box div{ font-weight:900; color:#0f172a; font-size:13px; }

  .ocr-card{
    margin-top:12px;
    border:1px dashed #cbd5e1;
    border-radius:16px;
    padding:12px;
    background:#f8fafc;
  }
  .ocr-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
  }
  .ocr-head .ttl{ font-weight:900; color:#0f172a; }
  .ocr-head .score{
    font-weight:900;
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
    background:#eef2ff;
    border:1px solid #c7d2fe;
    white-space:nowrap;
  }
  .ocr-note{
    font-size:12px;
    color:#475569;
    font-weight:700;
    line-height:1.4;
    margin-bottom:10px;
  }
  .btn-ocr{
    width:100%;
    height:46px;
    border-radius:999px;
    border:1px solid rgba(37,99,235,.25);
    background:linear-gradient(180deg,#dbeafe,#c7d2fe);
    font-weight:900;
    cursor:pointer;
  }
  .ocr-err{
    margin-top:10px;
    background:#fee2e2;
    border:1px solid #fecaca;
    color:#991b1b;
    padding:10px 12px;
    border-radius:14px;
    font-weight:900;
    font-size:12px;
  }
  .ocr-ok{
    margin-top:10px;
    background:#dcfce7;
    border:1px solid #bbf7d0;
    color:#065f46;
    padding:10px 12px;
    border-radius:14px;
    font-weight:900;
    font-size:12px;
  }
  .ocr-text{
    margin-top:10px;
    border-radius:14px;
    padding:12px;
    background:#0b1220;
    color:#e5e7eb;
    font-size:12px;
    line-height:1.5;
    max-height:240px;
    overflow:auto;
    border:1px solid rgba(255,255,255,.08);
    white-space:pre-wrap;
  }

  .match-row{
    margin-top:8px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
  }
  .badge-mini{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:900;
    font-size:11px;
    padding:6px 10px;
    border-radius:999px;
    border:1px solid #e5e7eb;
    background:#fff;
    color:#0f172a;
  }
  .badge-mini.ok{ background:#dcfce7; border-color:#bbf7d0; color:#065f46; }
  .badge-mini.mid{ background:#fef9c3; border-color:#fde68a; color:#854d0e; }
  .badge-mini.bad{ background:#fee2e2; border-color:#fecaca; color:#991b1b; }

  .mini-debug{
    width:100%;
    margin-top:6px;
    font-size:11px;
    color:#475569;
    font-weight:800;
  }
  .mini-debug code{
    display:block;
    margin-top:4px;
    padding:8px 10px;
    border-radius:12px;
    background:#fff;
    border:1px solid #e5e7eb;
    max-height:80px;
    overflow:auto;
    white-space:pre-wrap;
  }

  .pd-right{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    overflow:hidden;
    width:100%;
    max-width:520px;
  }
  @media (max-width: 980px){
    .pd-right{ max-width:none; }
  }

  .cv-frame{
    width:100%;
    height:520px;
    border:0;
    display:block;
    background:#0b1220;
  }
  @media (max-width: 980px){ .cv-frame{ height:480px; } }
  @media (max-width: 560px){ .cv-frame{ height:420px; } }

  .cv-foot{
    padding:10px 12px;
    font-size:12px;
    color:#475569;
    font-weight:800;
    background:#f8fafc;
    border-top:1px solid #e5e7eb;
  }
</style>

<div class="pd-wrap">
  <div class="pd-left">
    <div class="pd-row">
      <div class="pd-box"><b>Nama</b><div><?= e($nama) ?></div></div>
      <div class="pd-box"><b>Posisi</b><div><?= e($posisi) ?></div></div>

      <div class="pd-box"><b>Email</b><div><?= e($email) ?></div></div>
      <div class="pd-box"><b>No. HP</b><div><?= e($telp) ?></div></div>

      <div class="pd-box"><b>Kota</b><div><?= e($kota) ?></div></div>
      <div class="pd-box"><b>Pendidikan</b><div><?= e($pendidikanFull ?: '-') ?></div></div>

      <div class="pd-box"><b>Tanggal Lahir</b><div><?= e($tglLahirView) ?></div></div>
      <div class="pd-box"><b>Umur</b><div><?= e($umurView) ?></div></div>
    </div>

    <div class="ocr-card">
      <div class="ocr-head">
        <div class="ttl">📄 Screening / OCR (0–100)</div>
        <div class="score" id="scoreBadge">Belum bisa dihitung</div>
      </div>

      <div class="ocr-note" id="ocrNote">
        Klik OCR untuk ambil teks dari PDF. Kalau PDF hasil scan gambar, teks bisa kosong — nanti mode OCR gambar akan dicoba (halaman 1) biar tetap ada hasil.
      </div>

      <div class="match-row" id="matchRow" style="display:none;">
        <div class="badge-mini" id="expMeta">Exp: -</div>
        <div class="badge-mini" id="skillMeta">Skill+Sertif: -</div>
        <div class="badge-mini" id="matchBadge">Match: -</div>
      </div>

      <div class="mini-debug" id="debugBox" style="display:none;">
        Detected pengalaman (hasil parsing tanggal):
        <code id="debugRanges">-</code>
      </div>

      <?php if(!$cvFound): ?>
        <div class="ocr-err">CV tidak ada / kosong.</div>
      <?php else: ?>
        <button class="btn-ocr" type="button" id="btnOCR">🔎 OCR (Gratis)</button>
        <div id="ocrMsg"></div>
        <div class="ocr-text" id="ocrText" style="display:none;"></div>
        <div style="margin-top:8px; font-size:11px; font-weight:800; color:#64748b;">
          *OCR jalan di device HR (browser), bukan di server.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="pd-right">
    <?php if($cvFound): ?>
      <iframe class="cv-frame" id="cvFrame" src="<?= e($cvProxy) ?>"></iframe>
      <div class="cv-foot">
        *CV tampil otomatis di sini (inline), tidak download.<br>
        cvRaw: <?= e($cvRaw) ?><br>
        Resolved: <?= e($cvUrl) ?> (<?= $cvFound ? 'found' : 'missing' ?>)
      </div>
    <?php else: ?>
      <div style="padding:14px" class="modal-error">CV tidak ditemukan.</div>
    <?php endif; ?>
  </div>
</div>

<?php if($cvFound): ?>
<script type="module">
  const PDFJS_LIB   = "/career/admin/_vendor/pdfjs/pdfjs-4.7.76-dist/build/pdf.mjs";
  const PDFJS_WORKER= "/career/admin/_vendor/pdfjs/pdfjs-4.7.76-dist/build/pdf.worker.mjs";

  const CV_URL = <?= json_encode($cvProxy, JSON_UNESCAPED_SLASHES); ?>;

  const LOW_POSISI   = <?= json_encode((string)$posisi, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const LOW_JOBDESK  = <?= json_encode((string)$jobDesk, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const LOW_KRITERIA = <?= json_encode((string)$kriteria, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

  const btn = document.getElementById('btnOCR');
  const msg = document.getElementById('ocrMsg');
  const out = document.getElementById('ocrText');
  const badge = document.getElementById('scoreBadge');

  const matchRow   = document.getElementById('matchRow');
  const expMeta    = document.getElementById('expMeta');
  const skillMeta  = document.getElementById('skillMeta');
  const matchBadge = document.getElementById('matchBadge');

  const debugBox   = document.getElementById('debugBox');
  const debugRanges= document.getElementById('debugRanges');

  function setErr(t){ msg.innerHTML = `<div class="ocr-err">${t}</div>`; }
  function setOk(t){ msg.innerHTML = `<div class="ocr-ok">${t}</div>`; }
  function setLoading(t){ msg.innerHTML = `<div class="modal-loading" style="margin-top:10px">${t}</div>`; }

  function _clamp(n,a,b){ return Math.max(a, Math.min(b, n)); }

  // Normalisasi teks OCR biar parser tahan banting:
  // - samakan dash (– — -> -)
  // - buang kotak □
  // - rapihin spasi
  function _cleanText(s){
    return (s||"")
      .replace(/\u00a0/g,' ')
      .replace(/[–—−]/g,'-')
      .replace(/[□■•●]/g,' ')
      .replace(/\s+/g,' ')
      .trim();
  }

  function _norm(s){
    return _cleanText(s).toLowerCase();
  }

  function _parseMonthIndex(m){
    if(!m) return null;
    const s = String(m).toLowerCase().replace(/\./g,'').trim();
    const map = {
      jan:1, januari:1,
      feb:2, februari:2,
      mar:3, maret:3,
      apr:4, april:4,
      mei:5,
      jun:6, juni:6,
      jul:7, juli:7,
      agu:8, agt:8, agustus:8, aug:8, august:8,
      sep:9, september:9,
      okt:10, oktober:10, oct:10, october:10,
      nov:11, november:11,
      des:12, desember:12, dec:12, december:12
    };
    if(map[s]) return map[s];
    const n = parseInt(s,10);
    if(!isNaN(n) && n>=1 && n<=12) return n;
    return null;
  }

  function _nowYM(){
    const d = new Date();
    return { y: d.getFullYear(), m: d.getMonth()+1 };
  }
  function _ymToIndex(y,m){ return (y*12)+m; }

  // Fokus pengalaman: kalau ada heading "experience/pengalaman" ambil bagian itu
  function _sliceExperienceSection(raw){
    const t = _norm(raw);
    if(!t) return "";

    const startKeys = ["pengalaman kerja","work experience","pengalaman","experience"];
    const endKeys = ["pendidikan","education","keterampilan","skills","sertifikat","certificate","organisasi","organization","pelatihan","training","bahasa","languages","kontak","contact"];

    let start = -1;
    for(const k of startKeys){
      const i = t.indexOf(k);
      if(i !== -1 && (start === -1 || i < start)) start = i;
    }
    if(start === -1){
      return t; // fallback full
    }

    let end = -1;
    for(const k of endKeys){
      const i = t.indexOf(k, start + 5);
      if(i !== -1 && (end === -1 || i < end)) end = i;
    }
    return (end !== -1) ? t.slice(start, end) : t.slice(start);
  }

  function _formatYM(idx){
    const y = Math.floor(idx/12);
    const m = idx % 12;
    const mm = m === 0 ? 12 : m;
    const yy = m === 0 ? y-1 : y;
    const names = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
    return `${names[mm-1]} ${yy}`;
  }

  function _extractExperienceMetrics(text){
    const raw = _cleanText(text);
    const full = _norm(text);
    const expText = _sliceExperienceSection(text);
    const t = _cleanText(expText).toLowerCase();

    const cur = _nowYM();
    const durations = [];
    const pairs = [];
    const debug = [];

    // Helper: parse end token "saat ini/present/now/sekarang"
    function isNowToken(s){
      return (s === 'saat ini' || s === 'sekarang' || s === 'present' || s === 'now');
    }

    // (1) Rentang Month Year - Month Year / Now
    // dibuat longgar: dash bisa "-" dan spasi bisa berantakan
    {
      const re = /\b(jan|januari|feb|februari|mar|maret|apr|april|mei|jun|juni|jul|juli|agu|agt|agustus|aug|sep|september|okt|oktober|oct|october|nov|november|des|desember|dec|december)\s*(19\d{2}|20\d{2})\s*(?:\-|to|sd|s\/d|sampai)\s*(?:(jan|januari|feb|februari|mar|maret|apr|april|mei|jun|juni|jul|juli|agu|agt|agustus|aug|sep|september|okt|oktober|oct|october|nov|november|des|desember|dec|december)\s*(19\d{2}|20\d{2})|(saat\s*ini|sekarang|present|now))\b/g;
      let m;
      while((m = re.exec(t))){
        const m1 = _parseMonthIndex(m[1]);
        const y1 = parseInt(m[2],10);
        let m2,y2;

        if(m[5]){
          m2 = cur.m; y2 = cur.y;
        }else{
          m2 = _parseMonthIndex(m[3]);
          y2 = parseInt(m[4],10);
        }

        if(m1 && y1 && m2 && y2){
          const s = _ymToIndex(y1,m1);
          const e = _ymToIndex(y2,m2);
          let diff = (e - s) + 1;
          if(diff < 0) diff = 0;
          if(diff > 0 && diff <= 900){
            durations.push(diff);
            pairs.push([s,e]);
            debug.push(`${_formatYM(s)} - ${_formatYM(e)}`);
          }
        }
      }
    }

    // (2) Rentang dd Month yyyy - dd Month yyyy / now (lebih longgar juga)
    {
      const re = /\b(\d{1,2})\s*(jan|januari|feb|februari|mar|maret|apr|april|mei|jun|juni|jul|juli|agu|agt|agustus|aug|sep|september|okt|oktober|oct|october|nov|november|des|desember|dec|december)\s*(19\d{2}|20\d{2})\s*(?:\-|to|sd|s\/d|sampai)\s*(?:(\d{1,2})\s*(jan|januari|feb|februari|mar|maret|apr|april|mei|jun|juni|jul|juli|agu|agt|agustus|aug|sep|september|okt|oktober|oct|october|nov|november|des|desember|dec|december)\s*(19\d{2}|20\d{2})|(saat\s*ini|sekarang|present|now))\b/g;
      let m;
      while((m = re.exec(t))){
        const m1 = _parseMonthIndex(m[2]);
        const y1 = parseInt(m[3],10);
        let m2,y2;

        if(m[8]){
          m2 = cur.m; y2 = cur.y;
        }else{
          m2 = _parseMonthIndex(m[6]);
          y2 = parseInt(m[7],10);
        }

        if(m1 && y1 && m2 && y2){
          const s = _ymToIndex(y1,m1);
          const e = _ymToIndex(y2,m2);
          let diff = (e - s) + 1;
          if(diff < 0) diff = 0;
          if(diff > 0 && diff <= 900){
            durations.push(diff);
            pairs.push([s,e]);
            debug.push(`${_formatYM(s)} - ${_formatYM(e)}`);
          }
        }
      }
    }

    // (3) Year range: 2018 - 2022 / now
    {
      const re = /\b(19\d{2}|20\d{2})\s*(?:\-|to|sd|s\/d|sampai)\s*\b(19\d{2}|20\d{2}|saat\s*ini|sekarang|present|now)\b/g;
      let m;
      while((m = re.exec(t))){
        const y1 = parseInt(m[1],10);
        const endRaw = (m[2]||"").replace(/\s+/g,' ').trim();
        let y2;
        if(isNowToken(endRaw)){
          y2 = cur.y;
        }else{
          y2 = parseInt(endRaw,10);
        }
        if(y1 && y2 && y2 >= y1 && (y2-y1) <= 80){
          const s = _ymToIndex(y1,1);
          const e = _ymToIndex(y2,12);
          const diff = (e - s) + 1;
          durations.push(diff);
          pairs.push([s,e]);
          debug.push(`${y1} - ${y2}`);
        }
      }
    }

    // (4) Durasi eksplisit
    {
      const reMonth = /(\d{1,3})\s*(bulan|bln|mos|months)\b/g;
      let m;
      while((m = reMonth.exec(t))){
        const v = parseInt(m[1],10);
        if(!isNaN(v) && v>0 && v<=240){
          durations.push(v);
        }
      }
      const reYear = /(\d{1,2})\s*(tahun|thn|yrs|years)\b/g;
      while((m = reYear.exec(t))){
        const v = parseInt(m[1],10);
        if(!isNaN(v) && v>0 && v<=60){
          durations.push(v*12);
        }
      }
    }

    // Bersihin & batasi biar gak kebablasan
    const clean = durations
      .map(x => parseInt(x,10))
      .filter(x => !isNaN(x) && x>0 && x<=900)
      .slice(0, 12);

    const jobCount = clean.length;

    let avgMonths = 0;
    let shortCount = 0;
    if(jobCount > 0){
      let sum = 0;
      for(const d of clean){
        sum += d;
        if(d <= 6) shortCount += 1;
      }
      avgMonths = sum / jobCount;
    }
    const shortRatio = (jobCount > 0) ? (shortCount / jobCount) : 0;

    let spanMonths = 0;
    if(pairs.length > 0){
      let minS = Infinity, maxE = -Infinity;
      for(const [s,e] of pairs){
        if(s < minS) minS = s;
        if(e > maxE) maxE = e;
      }
      if(isFinite(minS) && isFinite(maxE) && maxE >= minS){
        spanMonths = (maxE - minS) + 1;
      }
    }
    if(spanMonths <= 0 && clean.length > 0){
      spanMonths = Math.max(...clean);
    }

    return { jobCount, avgMonths, shortRatio, spanMonths, durations: clean, full, expText: t, debug };
  }

  function _scoreSkillCert(text){
    const t = _norm(text);
    if(!t) return { score: 0, hits: 0 };

    let s = 0;
    let hits = 0;

    // section hints
    const sectionHints = ["keahlian","skill","skills","sertifikat","certificate","certification","pelatihan","training","course"];
    for(const k of sectionHints){
      if(t.includes(k)){ s += 3; hits++; break; }
    }

    // tools
    const tools = [
      "excel","spreadsheet","pivot","vlookup","hlookup",
      "word","powerpoint","ppt",
      "sap","erp","accurate","crm","salesforce",
      "sql","mysql","php","laravel","javascript","html","css"
    ];
    let toolHit = 0;
    for(const k of tools){
      if(t.includes(k)){
        toolHit++;
        if(toolHit <= 6) s += 2;
      }
    }
    hits += toolHit;

    // cert list
    const certs = ["bnsp","k3","toefl","ielts","brevet","iso","defensive driving","p3k","first aid","sim b","sim b1","sim b2","bi umum","b i umum","b1 umum","b2 umum"];
    let certHit = 0;
    for(const k of certs){
      if(t.includes(k)){
        certHit++;
        if(certHit <= 4) s += 3;
      }
    }
    hits += certHit;

    s = _clamp(s, 0, 20);
    return { score: s, hits };
  }

  function _keywordsFromLowongan(posisi, jobdesk, kriteria){
    const base = _norm([posisi, jobdesk, kriteria].filter(Boolean).join(" "));
    if(!base) return [];

    const stop = new Set([
      "dan","atau","yang","dengan","untuk","pada","dari","ke","di","sebagai","agar",
      "minimal","memiliki","mampu","dapat","wajib","lebih","diutamakan",
      "pengalaman","tahun","bulan","usia","laki","perempuan","pria","wanita",
      "sma","smk","d3","s1","s2","sederajat","jurusan","domisili"
    ]);

    const words = base.split(" ").map(w => w.trim()).filter(Boolean);
    const freq = new Map();

    for(let w of words){
      w = w.replace(/^[-\/\.]+|[-\/\.]+$/g,'');
      if(w.length < 3) continue;
      if(stop.has(w)) continue;
      if(/^\d+$/.test(w)) continue;
      freq.set(w, (freq.get(w)||0)+1);
    }

    const sorted = Array.from(freq.entries())
      .sort((a,b)=>b[1]-a[1])
      .map(x=>x[0]);

    return sorted.slice(0, 18);
  }

  function _scoreMatch(text, lowPos, lowJob, lowKri){
    const t = _norm(text);
    const keys = _keywordsFromLowongan(lowPos, lowJob, lowKri);
    if(keys.length === 0 || !t) return { score: 0, hit: 0, total: 0, keys };

    let hit = 0;
    for(const k of keys){
      if(t.includes(k)) hit++;
    }
    const total = keys.length;

    let sc = Math.round((hit / total) * 100);

    // bonus kecil kalau kata inti posisi muncul
    const posCore = _norm(lowPos).split(" ").filter(x=>x.length>=3);
    let coreHit = 0;
    for(const w of posCore){ if(t.includes(w)) coreHit++; }
    if(coreHit > 0) sc = Math.min(100, sc + 8);

    return { score: sc, hit, total, keys };
  }

  function _scoreExperience80(m){
    // kalau benar-benar gak ada deteksi: nilai minimal
    if(!m || m.jobCount === 0){
      return { score: 10, note: "exp_not_found" };
    }

    // countScore 0..30
    const countScore = _clamp(m.jobCount * 6, 8, 30);

    // spanScore 0..30 (lebih “human friendly”)
    const spanScore = _clamp(Math.round(Math.sqrt(_clamp(m.spanMonths, 1, 240)) * 2), 10, 30);

    // stability 0..20
    let stability = 0;
    if(m.avgMonths >= 36) stability = 20;
    else if(m.avgMonths >= 24) stability = 17;
    else if(m.avgMonths >= 18) stability = 14;
    else if(m.avgMonths >= 12) stability = 10;
    else if(m.avgMonths >= 8)  stability = 7;
    else if(m.avgMonths >= 6)  stability = 4;
    else stability = 2;

    // penalti hopping 0..18
    let hopPenalty = Math.round(18 * m.shortRatio);

    let score = countScore + spanScore + stability - hopPenalty;

    // tuning rules sesuai request lu:
    // A) banyak + span jauh => tinggi
    if(m.jobCount >= 4 && m.spanMonths >= 48) score += 8;

    // B) banyak tapi bentar2 => rendah
    if(m.jobCount >= 4 && m.avgMonths <= 6) score -= 12;

    // C) dikit tapi lama => lumayan besar
    if(m.jobCount <= 2 && m.avgMonths >= 18) score += 12;

    // D) dikit dan bentar => rendah banget
    if(m.jobCount <= 2 && m.avgMonths <= 6) score -= 12;

    score = _clamp(Math.round(score), 0, 80);
    return { score, note: "ok" };
  }

  function scoreSimple(text){
    const m = _extractExperienceMetrics(text);

    const exp = _scoreExperience80(m);        // 0..80
    const sk  = _scoreSkillCert(text);        // 0..20
    const total = _clamp(exp.score + sk.score, 0, 100);

    const mt  = _scoreMatch(text, LOW_POSISI, LOW_JOBDESK, LOW_KRITERIA);

    // tampil meta
    matchRow.style.display = 'flex';

    expMeta.textContent = `Exp: ${exp.score}/80 • job:${m.jobCount} • avg:${Math.round(m.avgMonths||0)}bln • span:${Math.round(m.spanMonths||0)}bln`;
    skillMeta.textContent = `Skill+Sertif: ${sk.score}/20`;
    matchBadge.textContent = `Match: ${mt.score}/100`;

    expMeta.classList.remove('ok','mid','bad');
    expMeta.classList.add('badge-mini');
    if(exp.score >= 55) expMeta.classList.add('ok');
    else if(exp.score >= 35) expMeta.classList.add('mid');
    else expMeta.classList.add('bad');

    skillMeta.classList.remove('ok','mid','bad');
    skillMeta.classList.add('badge-mini');
    if(sk.score >= 14) skillMeta.classList.add('ok');
    else if(sk.score >= 8) skillMeta.classList.add('mid');
    else skillMeta.classList.add('bad');

    matchBadge.classList.remove('ok','mid','bad');
    matchBadge.classList.add('badge-mini');
    if(mt.score >= 70) matchBadge.classList.add('ok');
    else if(mt.score >= 45) matchBadge.classList.add('mid');
    else matchBadge.classList.add('bad');

    // debug: tampilkan range yang ketangkep (biar lu bisa validasi cepat)
    if(m.debug && m.debug.length){
      debugBox.style.display = 'block';
      debugRanges.textContent = m.debug.slice(0, 10).join(" | ");
    }else{
      debugBox.style.display = 'block';
      debugRanges.textContent = "(tidak ada range yang ketangkep dari teks OCR)";
    }

    return total;
  }

  async function loadPDFJS(){
    const mod = await import(PDFJS_LIB);
    const pdfjsLib = mod;
    pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
    return pdfjsLib;
  }

  async function fetchArrayBuffer(url){
    const r = await fetch(url, { credentials: 'same-origin' });
    if(!r.ok) throw new Error(`Gagal ambil PDF (${r.status})`);
    const buf = await r.arrayBuffer();
    if(!buf || buf.byteLength < 50) throw new Error("PDF kosong / tidak ada konten");
    return buf;
  }

  async function extractTextFromPDF(pdfjsLib, data){
    const loadingTask = pdfjsLib.getDocument({ data });
    const pdf = await loadingTask.promise;

    let full = "";
    const maxPages = Math.min(pdf.numPages, 5);
    for(let p=1; p<=maxPages; p++){
      const page = await pdf.getPage(p);
      const tc = await page.getTextContent();
      const strings = tc.items.map(it => it.str).filter(Boolean);
      full += strings.join(" ") + "\n";
    }
    return { text: full.trim(), pdf };
  }

  async function ocrImageFirstPage(pdf){
    const TESS = "https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js";
    await new Promise((res, rej)=>{
      const s = document.createElement('script');
      s.src = TESS;
      s.onload = res;
      s.onerror = ()=>rej(new Error("Gagal load Tesseract (CDN diblok)."));
      document.head.appendChild(s);
    });

    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.6 });
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently:true });
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({ canvasContext: ctx, viewport }).promise;
    const dataUrl = canvas.toDataURL('image/png');

    const worker = await Tesseract.createWorker('ind+eng');
    const ret = await worker.recognize(dataUrl);
    await worker.terminate();
    return (ret?.data?.text || "").trim();
  }

  async function runOCR(){
    btn.disabled = true;
    out.style.display = 'none';
    out.textContent = '';
    badge.textContent = 'Memproses...';
    setLoading("Mengambil PDF...");

    try{
      const pdfjsLib = await loadPDFJS();
      const buf = await fetchArrayBuffer(CV_URL);

      setLoading("Membaca teks dari PDF...");
      const { text, pdf } = await extractTextFromPDF(pdfjsLib, buf);

      if(text && text.length > 40){
        const sc = scoreSimple(text);
        badge.textContent = `Skor: ${sc}/100`;
        setOk("Teks CV berhasil dibaca dari PDF.");
        out.textContent = _cleanText(text);
        out.style.display = 'block';
        return;
      }

      setLoading("PDF kemungkinan scan. Coba OCR gambar (halaman 1)...");
      const ocr = await ocrImageFirstPage(pdf);

      if(ocr && ocr.length > 40){
        const sc = scoreSimple(ocr);
        badge.textContent = `Skor: ${sc}/100`;
        setOk("OCR gambar berhasil (halaman 1).");
        out.textContent = _cleanText(ocr);
        out.style.display = 'block';
      }else{
        badge.textContent = 'Belum bisa dihitung';
        setErr("Teks masih kosong. Kemungkinan PDF scan/proteksi berat atau kualitas gambar buruk.");
      }

    }catch(err){
      badge.textContent = 'Belum bisa dihitung';
      setErr("OCR gagal: " + (err?.message || err));
    }finally{
      btn.disabled = false;
    }
  }

  btn?.addEventListener('click', runOCR);
</script>
<?php endif; ?>
