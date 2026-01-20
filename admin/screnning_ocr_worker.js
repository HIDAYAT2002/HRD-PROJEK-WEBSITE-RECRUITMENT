/* admin/screening_ocr_worker.js */
self.importScripts("https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js");

self.onmessage = async (e) => {
  const { jobId, images, lang } = e.data || {};
  try{
    const worker = await Tesseract.createWorker(lang || 'eng', 1, {
      logger: m => {
        if(m && m.status){
          self.postMessage({ jobId, type:'progress', status:m.status, progress: m.progress || 0 });
        }
      }
    });

    let fullText = "";
    for(let i=0;i<images.length;i++){
      self.postMessage({ jobId, type:'page', page:i+1, total:images.length });
      const { data } = await worker.recognize(images[i]);
      if(data && data.text) fullText += "\n" + data.text;
    }

    await worker.terminate();
    self.postMessage({ jobId, type:'done', text: fullText });
  }catch(err){
    self.postMessage({ jobId, type:'error', error: String(err && err.message ? err.message : err) });
  }
};
