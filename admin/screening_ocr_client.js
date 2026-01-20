async function screeningRunOCR(pelamarId, pdfUrl){
  // (1) cek cache dulu
  try{
    const cached = await fetch('screening_get_text.php?id=' + encodeURIComponent(pelamarId), { credentials:'same-origin' });
    const jc = await cached.json();
    if(jc && jc.ok && jc.text && jc.text.trim().length > 50){
      return { ok:true, text: jc.text, from:'cache' };
    }
  }catch(e){}

  // (2) jalankan OCR via worker
  let worker;
  try{
    worker = new Worker('screening_ocr_worker.js');
  }catch(e){
    return { ok:false, msg:'Worker tidak bisa dibuat. Pastikan screening_ocr_worker.js ada di folder admin.' };
  }

  const p = new Promise((resolve) => {
    worker.onmessage = (ev) => resolve(ev.data || {});
    worker.onerror = () => resolve({ ok:false, msg:'Worker error. Cek console (F12).' });
  });

  worker.postMessage({ id: pelamarId, pdfUrl });

  const res = await p;
  worker.terminate();

  if(!res || !res.ok || !res.text){
    return { ok:false, msg: (res && res.msg) ? res.msg : 'OCR gagal' };
  }

  // (3) simpan ke server (cache)
  try{
    const fd = new FormData();
    fd.append('id', pelamarId);
    fd.append('text', res.text);

    await fetch('screening_save_text.php', { method:'POST', body: fd, credentials:'same-origin' });
  }catch(e){}

  return { ok:true, text: res.text, from:'ocr' };
}
