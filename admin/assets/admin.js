// Admin SPA · vanilla JS. Carga content.json, renderiza secciones, guarda cambios.
(() => {
  'use strict';

  const API = 'api.php';
  const $ = (s, ctx = document) => ctx.querySelector(s);
  const $$ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
  const main = $('#adminMain');
  const nav = $('#adminNav');
  const toast = $('#toast');
  let state = null;       // contenido actual
  let currentSection = 'hero';

  // ─── Utilidades ───────────────────────────────────────────────────
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  // Convierte rutas relativas (img/foo.jpg) en absolutas (/img/foo.jpg) para que las imágenes
  // funcionen desde /admin/. Si ya es absoluta o http(s), se devuelve tal cual.
  const absUrl = (s) => {
    const v = String(s ?? '').trim();
    if (!v) return '';
    if (/^(https?:|data:|\/\/|\/)/i.test(v)) return v;
    return '/' + v.replace(/^\.\/+/, '');
  };
  // CSS url() seguro: previene inyección y mantiene el path correcto
  const cssUrl = (s) => `url("${absUrl(s).replace(/"/g, '%22')}")`;

  function showToast(msg, kind = 'ok') {
    toast.textContent = msg;
    toast.className = 'admin-toast is-' + kind;
    toast.hidden = false;
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => { toast.hidden = true; }, 2400);
  }

  async function api(action, opts = {}) {
    const url = `${API}?action=${encodeURIComponent(action)}`;
    const init = { method: opts.method || 'GET', headers: { 'X-CSRF-Token': window.CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (opts.json !== undefined) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.json); }
    if (opts.body) init.body = opts.body;
    const res = await fetch(url, init);
    if (res.status === 401) { location.href = 'login.php'; return; }
    const j = await res.json().catch(() => ({ ok: false, error: 'bad_json' }));
    if (!j.ok) throw new Error(j.error || 'api_error');
    return j;
  }

  let savedSnapshot = '';  // JSON congelado al cargar/guardar; permite detectar dirty.

  function isDirty() { try { return JSON.stringify(state) !== savedSnapshot; } catch { return false; } }

  async function loadAll() {
    const r = await api('all');
    state = r.data;
    if (!state) { state = {}; }
    savedSnapshot = JSON.stringify(state);
    render();
  }

  async function saveSection(section) {
    try {
      const data = state[section];
      await api('save_section', { method: 'POST', json: { section, data } });
      savedSnapshot = JSON.stringify(state);
      showToast(`✓ Cambios guardados`);
      flashSecHeader();
      if (typeof reloadPreview === 'function') reloadPreview();
    } catch (e) { showToast('Error al guardar: ' + e.message, 'err'); }
  }

  function flashSecHeader() {
    const h = document.querySelector('.sec-header');
    if (!h) return;
    h.classList.add('sec-header--saved');
    setTimeout(() => h.classList.remove('sec-header--saved'), 1800);
  }

  async function saveAll() {
    try {
      await api('save_all', { method: 'POST', json: { data: state } });
      savedSnapshot = JSON.stringify(state);
      showToast('✓ Todo guardado');
      if (typeof reloadPreview === 'function') reloadPreview();
    } catch (e) { showToast('Error: ' + e.message, 'err'); }
  }

  // Aviso antes de cerrar/recargar si hay cambios pendientes
  window.addEventListener('beforeunload', e => {
    if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
  });

  // ─── Navegación ───────────────────────────────────────────────────
  nav.addEventListener('click', e => {
    const btn = e.target.closest('.admin-nav__item');
    if (!btn) return;
    $$('.admin-nav__item', nav).forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    currentSection = btn.dataset.section;
    render();
  });

  // ─── Renderizado por sección ──────────────────────────────────────
  function render() {
    if (!state) { main.innerHTML = '<div class="admin-loading">Sin datos.</div>'; return; }
    const renderers = { hero, bio, members, discography, videos, concerts, press, instagram, photos, contact, form };
    main.innerHTML = '';
    main.appendChild(renderers[currentSection]());
  }

  function secHeader(title, sub, actions = []) {
    const div = document.createElement('div'); div.className = 'sec-header';
    div.innerHTML = `<div><h2>${esc(title)}</h2><div class="sec-header__sub">${esc(sub)}</div></div>`;
    if (actions.length) {
      const wrap = document.createElement('div'); wrap.className = 'sec-header__actions';
      actions.forEach(a => wrap.appendChild(a));
      div.appendChild(wrap);
    }
    return div;
  }

  function saveBtn(section, label = 'Guardar cambios') {
    const b = document.createElement('button');
    b.className = 'btn btn--primary';
    b.textContent = label;
    b.addEventListener('click', async () => { b.disabled = true; b.textContent = 'Guardando…'; await saveSection(section); b.disabled = false; b.textContent = label; });
    return b;
  }

  // ─── HERO ────────────────────────────────────────────────────────
  function hero() {
    const h = state.hero || (state.hero = {});
    const root = document.createElement('div');
    root.appendChild(secHeader('Portada', 'Lo primero que se ve al entrar en la web — texto grande y vídeo de fondo.', [saveBtn('hero')]));
    const card = document.createElement('div'); card.className = 'card';
    card.innerHTML = `
      <h3 class="card__title">Texto principal</h3>
      <label class="field">
        <span>Etiqueta de género (pequeña, encima del lema)</span>
        <input class="input" id="hero_label" placeholder="Pop-Rock Alternativo / Punk — Madrid" value="${esc(h.label || '')}">
      </label>
      <label class="field">
        <span>Lema / Tagline (texto grande, puedes saltar de línea con Enter)</span>
        <textarea class="textarea" id="hero_tagline" rows="3" placeholder="No te llenaré estadios pero un escenario sí.">${esc(h.tagline || '')}</textarea>
      </label>
      <label class="field">
        <span>Badge inferior (botoncito decorativo)</span>
        <input class="input" id="hero_badge" placeholder="MUERTE AL PAN!" value="${esc(h.badge || '')}">
      </label>
    `;
    root.appendChild(card);
    const videoCard = document.createElement('div'); videoCard.className = 'card';
    videoCard.innerHTML = `
      <h3 class="card__title">Vídeo de fondo</h3>
      <p class="card__hint">Pega solo el ID de YouTube (la parte después de <code>v=</code>). Por ejemplo, de <code>https://youtube.com/watch?v=<strong>jxkv3uXwY_I</strong></code>, copia <code>jxkv3uXwY_I</code>. El vídeo se reproduce en bucle y muteado.</p>
      <label class="field">
        <span>ID del vídeo de YouTube</span>
        <input class="input" id="hero_video" placeholder="jxkv3uXwY_I" value="${esc(h.videoId || '')}">
      </label>
    `;
    root.appendChild(videoCard);
    root.addEventListener('input', e => {
      const m = { hero_label: 'label', hero_tagline: 'tagline', hero_video: 'videoId', hero_badge: 'badge' };
      if (m[e.target.id]) h[m[e.target.id]] = e.target.value;
    });
    return root;
  }

  // ─── BIO ─────────────────────────────────────────────────────────
  function bio() {
    const b = state.bio || (state.bio = { paragraphs: [], stats: [], image: '' });
    const root = document.createElement('div');
    root.appendChild(secHeader('Biografía', 'Foto de cabecera, párrafos y estadísticas.', [saveBtn('bio')]));

    // Image picker
    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Foto de cabecera</h3>${renderImgPicker(b.image, src => { b.image = src; render(); })}`;
    root.appendChild(c1);

    // Paragraphs (HTML permitido)
    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Párrafos</h3>
      <p class="card__hint">Selecciona texto y usa los botones de formato (negrita, cursiva, enlace). Cada párrafo aparece como un bloque en la web.</p>`;
    const list = document.createElement('div'); list.className = 'list';
    (b.paragraphs || []).forEach((p, i) => list.appendChild(makeDraggable(paragraphItem(p, i, b), i)));
    makeSortable(list, (from, to) => moveItem(b.paragraphs, from, to));
    c2.appendChild(list);
    const addP = document.createElement('button'); addP.className = 'list-add'; addP.textContent = '+ Añadir párrafo';
    addP.addEventListener('click', () => { (b.paragraphs ||= []).push(''); render(); });
    c2.appendChild(addP);
    root.appendChild(c2);

    // Stats
    const c3 = document.createElement('div'); c3.className = 'card';
    c3.innerHTML = `<h3 class="card__title">Estadísticas (contadores)</h3>`;
    const sl = document.createElement('div'); sl.className = 'list';
    (b.stats || []).forEach((s, i) => sl.appendChild(makeDraggable(statItem(s, i, b), i)));
    makeSortable(sl, (from, to) => moveItem(b.stats, from, to));
    c3.appendChild(sl);
    const addS = document.createElement('button'); addS.className = 'list-add'; addS.textContent = '+ Añadir estadística';
    addS.addEventListener('click', () => { (b.stats ||= []).push({ number: 0, label: '' }); render(); });
    c3.appendChild(addS);
    root.appendChild(c3);

    return root;
  }

  function paragraphItem(p, i, parent) {
    const wrap = document.createElement('div'); wrap.className = 'list-item';
    const fieldEl = mkWysiwygField(`Párrafo ${i + 1}`, p, (newVal) => { parent.paragraphs[i] = newVal; }, { placeholder: 'Cuenta algo de la banda…' });
    wrap.appendChild(fieldEl);
    const actions = document.createElement('div'); actions.className = 'list-item__actions';
    if (i > 0) actions.appendChild(mkBtn('↑', '', () => { swap(parent.paragraphs, i, i - 1); render(); }));
    if (i < (parent.paragraphs.length - 1)) actions.appendChild(mkBtn('↓', '', () => { swap(parent.paragraphs, i, i + 1); render(); }));
    actions.appendChild(mkBtn('Eliminar', 'danger', () => { parent.paragraphs.splice(i, 1); render(); }));
    wrap.appendChild(actions);
    return wrap;
  }

  // ─── WYSIWYG mini-editor ─────────────────────────────────────────
  // Helper: crea un wrapper de campo con WYSIWYG. Devuelve el elemento HTML listo para insertar.
  function mkWysiwygField(label, value, onChange, opts = {}) {
    const wrap = document.createElement('label');
    wrap.className = 'field';
    const placeholder = opts.placeholder || 'Escribe aquí…';
    wrap.innerHTML = `
      <span>${esc(label)}</span>
      <div class="wysiwyg">
        <div class="wysiwyg__toolbar" role="toolbar" aria-label="Formato de texto">
          <button type="button" class="wysiwyg__btn" data-cmd="bold" title="Negrita (Ctrl+B)"><strong>B</strong></button>
          <button type="button" class="wysiwyg__btn" data-cmd="italic" title="Cursiva (Ctrl+I)"><em>I</em></button>
          <span class="wysiwyg__sep"></span>
          <button type="button" class="wysiwyg__btn" data-cmd="link" title="Enlace (Ctrl+K)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
          </button>
          <button type="button" class="wysiwyg__btn" data-cmd="unlink" title="Quitar enlace">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/><path d="M9 17H7a5 5 0 010-10h2"/><path d="M15 7h2a5 5 0 014.54 7"/></svg>
          </button>
          <span class="wysiwyg__sep"></span>
          <button type="button" class="wysiwyg__btn" data-cmd="clear" title="Quitar formato">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14M5 6l2 14M10 11v6M14 11v6"/></svg>
          </button>
        </div>
        <div class="wysiwyg__area" contenteditable="true" spellcheck="true" data-placeholder="${esc(placeholder)}"></div>
      </div>`;
    setupWysiwyg(wrap.querySelector('.wysiwyg'), value, onChange);
    return wrap;
  }

  function setupWysiwyg(root, initial, onChange) {
    const area = root.querySelector('.wysiwyg__area');
    area.innerHTML = initial || '';

    function sync() {
      const html = sanitizeWysiwyg(area.innerHTML);
      onChange(html);
    }
    area.addEventListener('input', sync);
    area.addEventListener('blur', sync);

    // Atajos
    area.addEventListener('keydown', e => {
      if (!(e.ctrlKey || e.metaKey)) return;
      const k = e.key.toLowerCase();
      if (k === 'b') { e.preventDefault(); document.execCommand('bold'); sync(); }
      else if (k === 'i') { e.preventDefault(); document.execCommand('italic'); sync(); }
      else if (k === 'k') { e.preventDefault(); insertLink(); }
    });

    function insertLink() {
      const sel = window.getSelection();
      const selText = sel && !sel.isCollapsed ? sel.toString() : '';
      const url = prompt('URL del enlace (https://…)', 'https://');
      if (!url || url === 'https://') return;
      if (selText) document.execCommand('createLink', false, url);
      else {
        const a = document.createElement('a');
        a.href = url; a.target = '_blank'; a.rel = 'noopener'; a.textContent = url;
        const range = sel && sel.rangeCount ? sel.getRangeAt(0) : null;
        if (range) range.insertNode(a);
        else area.appendChild(a);
      }
      sync();
    }

    // Toolbar
    root.querySelectorAll('.wysiwyg__btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        const cmd = btn.dataset.cmd;
        area.focus();
        if (cmd === 'bold' || cmd === 'italic') document.execCommand(cmd);
        else if (cmd === 'link') insertLink();
        else if (cmd === 'unlink') document.execCommand('unlink');
        else if (cmd === 'clear') document.execCommand('removeFormat');
        sync();
      });
    });

    // Paste: solo texto plano (sin formato basura de Word/Docs)
    area.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });
  }

  // Whitelist mini: solo <strong>, <em>, <b>, <i>, <a>, <br>. Resto se elimina.
  function sanitizeWysiwyg(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    const allow = new Set(['STRONG', 'EM', 'B', 'I', 'A', 'BR', 'P']);
    function walk(node) {
      [...node.childNodes].forEach(child => {
        if (child.nodeType === 1) {
          if (!allow.has(child.tagName)) {
            // Reemplazar por su contenido
            while (child.firstChild) node.insertBefore(child.firstChild, child);
            node.removeChild(child);
          } else {
            // Mantener solo atributos seguros
            const safeAttrs = child.tagName === 'A' ? ['href', 'target', 'rel'] : [];
            [...child.attributes].forEach(a => { if (!safeAttrs.includes(a.name)) child.removeAttribute(a.name); });
            if (child.tagName === 'A') {
              const href = child.getAttribute('href') || '';
              if (!/^(https?:|mailto:|#|\/)/i.test(href)) child.removeAttribute('href');
              child.setAttribute('target', '_blank');
              child.setAttribute('rel', 'noopener noreferrer');
            }
            walk(child);
          }
        } else if (child.nodeType !== 3 && child.nodeType !== 8) {
          node.removeChild(child);
        }
      });
    }
    walk(tmp);
    // Normalizar <p> a saltos: si todo el contenido va envuelto en <p>, sacarlo
    if (tmp.children.length === 1 && tmp.firstElementChild.tagName === 'P') {
      tmp.firstElementChild.replaceWith(...tmp.firstElementChild.childNodes);
    }
    return tmp.innerHTML.trim();
  }

  function statItem(s, i, parent) {
    const wrap = document.createElement('div'); wrap.className = 'list-item';
    wrap.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>Número</span><input class="input" type="number" value="${esc(s.number ?? 0)}" data-k="number"></label>
      <label class="field"><span>Etiqueta</span><input class="input" value="${esc(s.label || '')}" data-k="label"></label>
    </div>`;
    wrap.addEventListener('input', e => {
      const k = e.target.dataset.k; if (!k) return;
      parent.stats[i][k] = k === 'number' ? parseInt(e.target.value || '0', 10) : e.target.value;
    });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    a.appendChild(mkBtn('Eliminar', 'danger', () => { parent.stats.splice(i, 1); render(); }));
    wrap.appendChild(a);
    return wrap;
  }

  // ─── MEMBERS ─────────────────────────────────────────────────────
  function members() {
    const arr = state.members || (state.members = []);
    const root = document.createElement('div');
    root.appendChild(secHeader('Miembros de la banda', 'Orden y nombres. El primero aparece arriba.', [saveBtn('members')]));
    const card = document.createElement('div'); card.className = 'card';
    const list = document.createElement('div'); list.className = 'list';
    arr.forEach((m, i) => list.appendChild(makeDraggable(memberItem(m, i, arr), i)));
    makeSortable(list, (from, to) => moveItem(arr, from, to));
    card.appendChild(list);
    const add = document.createElement('button'); add.className = 'list-add'; add.textContent = '+ Añadir miembro';
    add.addEventListener('click', () => { arr.push({ name: 'Nuevo', role: 'Instrumento' }); render(); });
    card.appendChild(add);
    root.appendChild(card);
    return root;
  }

  function memberItem(m, i, arr) {
    const w = document.createElement('div'); w.className = 'list-item';
    w.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>Nombre</span><input class="input" value="${esc(m.name || '')}" data-k="name"></label>
      <label class="field"><span>Rol / Instrumento</span><input class="input" value="${esc(m.role || '')}" data-k="role"></label>
    </div>`;
    w.addEventListener('input', e => { const k = e.target.dataset.k; if (k) arr[i][k] = e.target.value; });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    if (i > 0) a.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) a.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    a.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(a);
    return w;
  }

  // ─── DISCOGRAFÍA ─────────────────────────────────────────────────
  function discography() {
    const d = state.discography || (state.discography = { featured: {}, past: [] });
    const root = document.createElement('div');
    root.appendChild(secHeader('Discografía', 'Disco destacado y catálogo anterior.', [saveBtn('discography')]));

    // Featured
    const f = d.featured || (d.featured = {});
    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Disco destacado</h3>
      <div class="list-item__grid">
        <label class="field"><span>Año</span><input class="input" data-k="year" value="${esc(f.year || '')}"></label>
        <label class="field"><span>Título</span><input class="input" data-k="title" value="${esc(f.title || '')}"></label>
      </div>
      <label class="field"><span>Sello</span><input class="input" data-k="label" value="${esc(f.label || '')}"></label>
      <div class="field"><span class="field-label">Portada</span>${renderImgPicker(f.image, src => { f.image = src; render(); })}</div>
      <div id="featDescSlot"></div>
      <div class="list-item__grid">
        <label class="field"><span>Spotify Album ID (para el embed)</span><input class="input" data-k="spotifyId" value="${esc(f.spotifyId || '')}"></label>
        <label class="field"><span>Spotify URL (artista)</span><input class="input" data-k="spotifyUrl" value="${esc(f.spotifyUrl || '')}"></label>
        <label class="field"><span>Apple Music URL</span><input class="input" data-k="appleUrl" value="${esc(f.appleUrl || '')}"></label>
        <label class="field"><span>Bandcamp URL</span><input class="input" data-k="bandcampUrl" value="${esc(f.bandcampUrl || '')}"></label>
      </div>
      <div class="field"><span class="field-label">Tracklist</span></div>
      <div class="tracks" id="featTracks"></div>
      <button class="list-add" id="featAddTrack">+ Añadir canción</button>
    `;
    c1.addEventListener('input', e => { const k = e.target.dataset.k; if (k) f[k] = e.target.value; });
    // Inyectar WYSIWYG en el slot de descripción
    c1.querySelector('#featDescSlot').appendChild(mkWysiwygField('Descripción', f.description || '', html => { f.description = html; }));
    root.appendChild(c1);
    const tracks = c1.querySelector('#featTracks');
    (f.tracklist || []).forEach((t, i) => tracks.appendChild(trackRow(t, i, f, 'tracklist')));
    c1.querySelector('#featAddTrack').addEventListener('click', () => { (f.tracklist ||= []).push(''); render(); });

    // Past
    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Discos anteriores</h3>`;
    const list = document.createElement('div'); list.className = 'list';
    (d.past || []).forEach((a, i) => list.appendChild(makeDraggable(albumItem(a, i, d), i)));
    makeSortable(list, (from, to) => moveItem(d.past, from, to));
    c2.appendChild(list);
    const add = document.createElement('button'); add.className = 'list-add'; add.textContent = '+ Añadir álbum';
    add.addEventListener('click', () => { (d.past ||= []).push({ year: '', title: 'Nuevo', image: '', tracklist: [] }); render(); });
    c2.appendChild(add);
    root.appendChild(c2);
    return root;
  }

  function trackRow(t, i, parent, key) {
    const row = document.createElement('div'); row.className = 'track-row';
    row.innerHTML = `<span class="track-row__idx">${String(i + 1).padStart(2, '0')}</span><input class="input" value="${esc(t)}">`;
    row.querySelector('input').addEventListener('input', e => { parent[key][i] = e.target.value; });
    row.appendChild(mkBtn('×', 'ghost', () => { parent[key].splice(i, 1); render(); }));
    return row;
  }

  function albumItem(a, i, parentObj) {
    const arr = parentObj.past;
    const w = document.createElement('div'); w.className = 'list-item';
    w.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>Año</span><input class="input" data-k="year" value="${esc(a.year || '')}"></label>
      <label class="field"><span>Título</span><input class="input" data-k="title" value="${esc(a.title || '')}"></label>
      <label class="field"><span>Tipo (Single, EP, vacío = LP)</span><input class="input" data-k="type" value="${esc(a.type || '')}"></label>
    </div>
    <div class="field"><span class="field-label">Portada</span>${renderImgPicker(a.image, src => { a.image = src; render(); })}</div>
    <div class="field"><span class="field-label">Tracklist (opcional)</span></div>
    <div class="tracks" data-tracks="${i}"></div>
    <button class="list-add" data-add-track="${i}">+ Añadir canción</button>`;
    w.addEventListener('input', e => { const k = e.target.dataset.k; if (k) arr[i][k] = e.target.value; });
    const tr = w.querySelector(`[data-tracks="${i}"]`);
    (a.tracklist || []).forEach((t, ti) => tr.appendChild(trackRow(t, ti, arr[i], 'tracklist')));
    w.querySelector(`[data-add-track="${i}"]`).addEventListener('click', () => { (arr[i].tracklist ||= []).push(''); render(); });
    const ac = document.createElement('div'); ac.className = 'list-item__actions';
    if (i > 0) ac.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) ac.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    ac.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(ac);
    return w;
  }

  // ─── VÍDEOS ──────────────────────────────────────────────────────
  function videos() {
    const v = state.videos || (state.videos = { featured: {}, grid: [] });
    const root = document.createElement('div');
    root.appendChild(secHeader('Vídeos', 'Vídeo destacado y grid de YouTube.', [saveBtn('videos')]));

    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Vídeo destacado</h3>
      <div class="list-item__grid">
        <label class="field"><span>YouTube ID</span><input class="input" data-k="id" value="${esc(v.featured?.id || '')}"></label>
        <label class="field"><span>Título</span><input class="input" data-k="title" value="${esc(v.featured?.title || '')}"></label>
      </div>
      <div class="field"><span class="field-label">Miniatura</span>${renderImgPicker(v.featured?.thumb, src => { v.featured.thumb = src; render(); })}</div>`;
    c1.addEventListener('input', e => { const k = e.target.dataset.k; if (k) (v.featured ||= {})[k] = e.target.value; });
    root.appendChild(c1);

    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Grid de vídeos</h3>`;
    const list = document.createElement('div'); list.className = 'list';
    (v.grid || []).forEach((vv, i) => list.appendChild(makeDraggable(videoItem(vv, i, v), i)));
    makeSortable(list, (from, to) => moveItem(v.grid, from, to));
    c2.appendChild(list);
    const add = document.createElement('button'); add.className = 'list-add'; add.textContent = '+ Añadir vídeo';
    add.addEventListener('click', () => { (v.grid ||= []).push({ id: '', title: '', thumb: '' }); render(); });
    c2.appendChild(add);
    root.appendChild(c2);
    return root;
  }

  function videoItem(vv, i, parent) {
    const arr = parent.grid;
    const w = document.createElement('div'); w.className = 'list-item';
    w.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>YouTube ID</span><input class="input" data-k="id" value="${esc(vv.id || '')}"></label>
      <label class="field"><span>Título</span><input class="input" data-k="title" value="${esc(vv.title || '')}"></label>
    </div>
    <div class="field"><span class="field-label">Miniatura</span>${renderImgPicker(vv.thumb, src => { arr[i].thumb = src; render(); })}</div>`;
    w.addEventListener('input', e => { const k = e.target.dataset.k; if (k) arr[i][k] = e.target.value; });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    if (i > 0) a.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) a.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    a.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(a);
    return w;
  }

  // ─── CONCIERTOS ──────────────────────────────────────────────────
  function concerts() {
    const c = state.concerts || (state.concerts = { upcoming: [], past: [] });
    const root = document.createElement('div');
    root.appendChild(secHeader('Conciertos', 'Próximos shows + histórico.', [saveBtn('concerts')]));

    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Próximos</h3>`;
    const lu = document.createElement('div'); lu.className = 'list';
    (c.upcoming || []).forEach((x, i) => lu.appendChild(makeDraggable(concertItem(x, i, c.upcoming, true), i)));
    makeSortable(lu, (from, to) => moveItem(c.upcoming, from, to));
    c1.appendChild(lu);
    const addU = document.createElement('button'); addU.className = 'list-add'; addU.textContent = '+ Añadir próximo concierto';
    addU.addEventListener('click', () => { (c.upcoming ||= []).push({ date: '', day: '', month: '', venue: '', city: '', status: 'Entradas', ticketsUrl: '' }); render(); });
    c1.appendChild(addU);
    root.appendChild(c1);

    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Pasados</h3>`;
    const lp = document.createElement('div'); lp.className = 'list';
    (c.past || []).forEach((x, i) => lp.appendChild(makeDraggable(concertItem(x, i, c.past, false), i)));
    makeSortable(lp, (from, to) => moveItem(c.past, from, to));
    c2.appendChild(lp);
    const addP = document.createElement('button'); addP.className = 'list-add'; addP.textContent = '+ Añadir concierto pasado';
    addP.addEventListener('click', () => { (c.past ||= []).push({ date: '', day: '', month: '', venue: '', city: '', status: 'Finalizado', url: '' }); render(); });
    c2.appendChild(addP);
    root.appendChild(c2);
    return root;
  }

  function concertItem(x, i, arr, upcoming) {
    const w = document.createElement('div'); w.className = 'list-item';
    const urlField = upcoming ? 'ticketsUrl' : 'url';
    const urlLabel = upcoming ? 'Enlace para comprar entradas' : 'Enlace al show o reseña (opcional)';
    const statusOptions = upcoming
      ? ['Entradas', 'Pronto', 'Sold out', 'Cancelado']
      : ['Finalizado', 'Agotado', 'Publicado', 'Emitido'];
    w.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>Fecha del concierto</span><input class="input" data-k="date" type="date" value="${esc(x.date || '')}"></label>
      <label class="field"><span>Estado</span>
        <select class="input" data-k="status">
          ${statusOptions.map(s => `<option ${s === x.status ? 'selected' : ''}>${esc(s)}</option>`).join('')}
        </select>
      </label>
    </div>
    <label class="field"><span>Sala / Venue</span><input class="input" data-k="venue" placeholder="Sala El Sótano" value="${esc(x.venue || '')}"></label>
    <label class="field"><span>Ciudad y detalles</span><input class="input" data-k="city" placeholder='C/ Maldonadas 6, Madrid — 21:00' value="${esc(x.city || '')}"></label>
    <label class="field"><span>${esc(urlLabel)}</span><input class="input" data-k="${urlField}" placeholder="https://..." value="${esc(x[urlField] || '')}"></label>`;

    // Listener inputs + auto-cálculo de day/month desde date
    const MONTHS_ABBR = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    w.addEventListener('input', e => {
      const k = e.target.dataset.k;
      if (!k) return;
      arr[i][k] = e.target.value;
      if (k === 'date' && /^\d{4}-\d{2}-\d{2}/.test(e.target.value)) {
        const d = new Date(e.target.value + 'T12:00:00');
        arr[i].day = String(d.getDate()).padStart(2, '0');
        arr[i].month = MONTHS_ABBR[d.getMonth()];
      }
    });
    w.addEventListener('change', e => {
      const k = e.target.dataset.k;
      if (k === 'status') arr[i].status = e.target.value;
    });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    if (i > 0) a.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) a.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    a.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(a);
    return w;
  }

  // ─── PRENSA ──────────────────────────────────────────────────────
  function press() {
    const p = state.press || (state.press = { quotes: [], articles: [] });
    const root = document.createElement('div');
    root.appendChild(secHeader('Prensa', 'Citas destacadas y artículos.', [saveBtn('press')]));

    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Citas destacadas</h3>`;
    const lq = document.createElement('div'); lq.className = 'list';
    (p.quotes || []).forEach((q, i) => lq.appendChild(makeDraggable(quoteItem(q, i, p.quotes), i)));
    makeSortable(lq, (from, to) => moveItem(p.quotes, from, to));
    c1.appendChild(lq);
    const addQ = document.createElement('button'); addQ.className = 'list-add'; addQ.textContent = '+ Añadir cita';
    addQ.addEventListener('click', () => { (p.quotes ||= []).push({ text: '', source: '', url: '' }); render(); });
    c1.appendChild(addQ);
    root.appendChild(c1);

    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Artículos / Noticias</h3>
      <div style="display:flex;gap:8px;margin-bottom:14px">
        <input class="input" id="prUrlImport" placeholder="https://medio.com/articulo — pega URL y pulsa Importar" style="flex:1">
        <button class="btn btn--outline" id="prUrlImportBtn">Importar desde URL</button>
      </div>`;
    const la = document.createElement('div'); la.className = 'list';
    (p.articles || []).forEach((a, i) => la.appendChild(makeDraggable(articleItem(a, i, p.articles), i)));
    makeSortable(la, (from, to) => moveItem(p.articles, from, to));
    c2.appendChild(la);
    const addA = document.createElement('button'); addA.className = 'list-add'; addA.textContent = '+ Añadir artículo manual';
    addA.addEventListener('click', () => { (p.articles ||= []).unshift({ date: '', dateLabel: '', source: '', logo: '', title: '', url: '' }); render(); });
    c2.appendChild(addA);

    // URL import handler
    c2.querySelector('#prUrlImportBtn').addEventListener('click', async () => {
      const inp = c2.querySelector('#prUrlImport');
      const url = inp.value.trim();
      if (!url) { showToast('Pega una URL primero', 'err'); return; }
      const btn = c2.querySelector('#prUrlImportBtn');
      btn.disabled = true; btn.textContent = 'Importando…';
      try {
        const r = await api('fetch_url_meta', { method: 'POST', json: { url } });
        (p.articles ||= []).unshift({
          date: r.data.date || '',
          dateLabel: r.data.dateLabel || new Date().getFullYear().toString(),
          source: r.data.source || '',
          logo: r.data.logo || '',
          title: r.data.title || '(sin título)',
          url: r.data.url || url,
        });
        inp.value = '';
        showToast('✓ Artículo importado');
        render();
      } catch (e) {
        showToast('No se pudo extraer: ' + e.message, 'err');
      } finally {
        btn.disabled = false; btn.textContent = 'Importar desde URL';
      }
    });

    root.appendChild(c2);
    return root;
  }

  function quoteItem(q, i, arr) {
    const w = document.createElement('div'); w.className = 'list-item';
    w.innerHTML = `<div id="qSlot"></div>
    <div class="list-item__grid">
      <label class="field"><span>Fuente</span><input class="input" data-k="source" value="${esc(q.source || '')}"></label>
      <label class="field"><span>URL</span><input class="input" data-k="url" value="${esc(q.url || '')}"></label>
    </div>`;
    w.querySelector('#qSlot').appendChild(mkWysiwygField('Cita', q.text || '', html => { arr[i].text = html; }));
    w.addEventListener('input', e => { const k = e.target.dataset.k; if (k) arr[i][k] = e.target.value; });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    if (i > 0) a.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) a.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    a.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(a);
    return w;
  }

  function articleItem(a, i, arr) {
    const w = document.createElement('div'); w.className = 'list-item';
    w.innerHTML = `<div class="list-item__grid">
      <label class="field"><span>Fecha ISO</span><input class="input" data-k="date" value="${esc(a.date || '')}"></label>
      <label class="field"><span>Fecha visible</span><input class="input" data-k="dateLabel" value="${esc(a.dateLabel || '')}"></label>
      <label class="field"><span>Medio / fuente</span><input class="input" data-k="source" value="${esc(a.source || '')}"></label>
      <label class="field"><span>Logo (texto corto)</span><input class="input" data-k="logo" value="${esc(a.logo || '')}"></label>
    </div>
    <label class="field"><span>Título</span><input class="input" data-k="title" value="${esc(a.title || '')}"></label>
    <label class="field"><span>URL</span><input class="input" data-k="url" value="${esc(a.url || '')}"></label>`;
    w.addEventListener('input', e => { const k = e.target.dataset.k; if (k) arr[i][k] = e.target.value; });
    const ac = document.createElement('div'); ac.className = 'list-item__actions';
    if (i > 0) ac.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) ac.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    ac.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(ac);
    return w;
  }

  // ─── FOTOS ───────────────────────────────────────────────────────
  // ─── FOTOS — gestor visual ─────────────────────────────────────
  let photoSearch = '';
  let photoSelection = new Set();

  function photos() {
    const arr = state.photos || (state.photos = []);
    const root = document.createElement('div');

    // Header con botones
    const uploadBtn = document.createElement('button');
    uploadBtn.className = 'btn btn--primary';
    uploadBtn.innerHTML = '↑ Subir fotos';
    uploadBtn.addEventListener('click', () => {
      const inp = document.createElement('input');
      inp.type = 'file'; inp.multiple = true; inp.accept = 'image/*';
      inp.addEventListener('change', () => uploadFiles(inp.files, true));
      inp.click();
    });
    root.appendChild(secHeader('Fotos', `${arr.length} fotos en la galería · arrastra ⋮⋮ para reordenar · suelta archivos en cualquier sitio para subir`, [uploadBtn, saveBtn('photos')]));

    // Toolbar (search + acciones batch si hay seleccion)
    const toolbar = document.createElement('div'); toolbar.className = 'photos-toolbar';
    toolbar.innerHTML = `
      <input class="input" id="photoSearch" type="search" placeholder="Buscar por nombre…" value="${esc(photoSearch)}" style="max-width:260px">
      <div class="photos-batch" ${photoSelection.size ? '' : 'hidden'}>
        <span class="photos-batch__count">${photoSelection.size} seleccionada${photoSelection.size === 1 ? '' : 's'}</span>
        <button class="btn btn--ghost btn--sm" data-batch="wide">▭ Wide</button>
        <button class="btn btn--ghost btn--sm" data-batch="normal">▢ Normal</button>
        <button class="btn btn--ghost btn--sm" data-batch="tall">▯ Tall</button>
        <button class="btn btn--danger btn--sm" data-batch="delete">Eliminar</button>
        <button class="btn btn--ghost btn--sm" data-batch="clear">Limpiar</button>
      </div>`;
    toolbar.querySelector('#photoSearch').addEventListener('input', e => {
      photoSearch = e.target.value.toLowerCase();
      render();
    });
    toolbar.addEventListener('click', e => {
      const act = e.target?.dataset?.batch; if (!act) return;
      if (act === 'clear') { photoSelection.clear(); render(); return; }
      if (act === 'delete') {
        if (!confirm(`¿Eliminar ${photoSelection.size} fotos seleccionadas?`)) return;
        state.photos = arr.filter((_, idx) => !photoSelection.has(idx));
        photoSelection.clear(); render(); return;
      }
      // change size
      photoSelection.forEach(idx => { if (arr[idx]) arr[idx].size = act; });
      render();
    });
    root.appendChild(toolbar);

    // Empty state
    if (!arr.length) {
      const empty = document.createElement('div'); empty.className = 'photos-empty';
      empty.innerHTML = `<div style="font-size:48px;opacity:.3">⊞</div><h3>Galería vacía</h3><p>Pulsa <strong>Subir fotos</strong> o arrastra archivos aquí.</p>`;
      root.appendChild(empty);
    } else {
      const grid = document.createElement('div'); grid.className = 'photos-admin';
      arr.forEach((ph, i) => {
        // Filtro por busqueda
        const hay = photoSearch === '' ||
                    (ph.src || '').toLowerCase().includes(photoSearch) ||
                    (ph.caption || '').toLowerCase().includes(photoSearch);
        if (!hay) return;
        grid.appendChild(makeDraggable(photoTile(ph, i, arr), i));
      });
      makeSortable(grid, (from, to) => { moveItem(arr, from, to); photoSelection.clear(); });
      root.appendChild(grid);
    }

    // Drop area global (siempre visible aunque haya fotos)
    const dz = document.createElement('label'); dz.className = 'photos-dropzone';
    dz.innerHTML = `<input type="file" multiple accept="image/*"><div><strong>+ Suelta imágenes aquí o haz clic</strong><br><span style="opacity:.7">JPG · PNG · WebP · GIF — hasta 12 MB · se optimizan automáticamente</span></div>`;
    const inp = dz.querySelector('input');
    inp.addEventListener('change', () => uploadFiles(inp.files, true));
    ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('is-drag'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('is-drag'); }));
    dz.addEventListener('drop', e => { e.preventDefault(); if (e.dataTransfer?.files?.length) uploadFiles(e.dataTransfer.files, true); });
    root.appendChild(dz);

    return root;
  }

  function photoTile(ph, i, arr) {
    const w = document.createElement('div');
    w.className = 'photo-tile' + (photoSelection.has(i) ? ' is-selected' : '') + (ph._progress === 'uploading' ? ' is-uploading' : '') + (ph._progress === 'error' ? ' is-upload-error' : '');
    const fileName = (ph.src || '').split('/').pop();
    const sizeClass = ph.size || 'normal';
    const sizeLabels = { normal: 'Cuadrada', wide: 'Panorámica', tall: 'Vertical' };
    w.innerHTML = `
      <div class="photo-tile__media" data-act="zoom" title="Click para ver grande">
        <img src="${esc(absUrl(ph.src))}" alt="" loading="lazy">
        <span class="photo-tile__order" title="Posición ${i + 1}">${i + 1}</span>
        <span class="photo-tile__size" title="Tamaño en galería">${esc(sizeLabels[sizeClass] || sizeClass)}</span>
        <span class="photo-tile__handle" title="Arrastra para reordenar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
        </span>
        <label class="photo-tile__check" title="Seleccionar para acción en lote">
          <input type="checkbox" ${photoSelection.has(i) ? 'checked' : ''} data-act="select">
          <span class="photo-tile__check-box"></span>
        </label>
        <div class="photo-tile__upload-overlay">
          <div class="photo-tile__spinner"></div>
          <span class="photo-tile__upload-text">Subiendo…</span>
        </div>
      </div>
      <div class="photo-tile__body">
        <input class="input photo-tile__caption" placeholder="Descripción de la foto" value="${esc(ph.caption || '')}" data-k="caption" type="text">
        <div class="photo-tile__meta">
          <div class="photo-tile__size-picker" title="Tamaño en galería">
            <button class="photo-tile__sz ${sizeClass === 'wide' ? 'is-active' : ''}" data-act="size-wide" title="Panorámica">
              <svg width="20" height="14" viewBox="0 0 20 14" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="18" height="8" rx="1"/></svg>
            </button>
            <button class="photo-tile__sz ${sizeClass === 'normal' ? 'is-active' : ''}" data-act="size-normal" title="Cuadrada">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="1" width="12" height="12" rx="1"/></svg>
            </button>
            <button class="photo-tile__sz ${sizeClass === 'tall' ? 'is-active' : ''}" data-act="size-tall" title="Vertical">
              <svg width="10" height="14" viewBox="0 0 10 14" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="1" width="8" height="12" rx="1"/></svg>
            </button>
          </div>
          <button class="photo-tile__btn photo-tile__btn--danger" data-act="del" title="Eliminar de la galería">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
          </button>
        </div>
      </div>`;

    // Caption: el input NO debe arrastrar
    const captionEl = w.querySelector('input[data-k=caption]');
    captionEl.addEventListener('input', e => { arr[i].caption = e.target.value; arr[i].alt = e.target.value; });
    captionEl.addEventListener('mousedown', e => e.stopPropagation());
    captionEl.addEventListener('pointerdown', e => e.stopPropagation());
    captionEl.addEventListener('dragstart', e => e.preventDefault());

    // El handle es el unico draggable real (todo el tile es draggable pero el ratón solo se "activa" en el handle visual)
    // Los inputs y checkboxes detienen propagacion

    // Clicks: dispatch por data-act
    w.addEventListener('click', e => {
      const target = e.target.closest('[data-act]');
      if (!target) return;
      const act = target.dataset.act;
      if (act === 'select') {
        if (target.checked) photoSelection.add(i); else photoSelection.delete(i);
        // Actualizar visual sin re-render completo
        w.classList.toggle('is-selected', target.checked);
        renderBatchBar();
        return;
      }
      if (act === 'zoom') {
        // No abrir lightbox si clic vino del checkbox o handle
        if (e.target.matches('input,label,.photo-tile__handle,.photo-tile__check,.photo-tile__check-box')) return;
        openPhotoLightbox(ph, arr.filter(x => !photoSearch || ((x.src||'').toLowerCase().includes(photoSearch) || (x.caption||'').toLowerCase().includes(photoSearch))));
        return;
      }
      if (act === 'del') {
        if (!confirm('¿Eliminar esta foto de la galería?')) return;
        arr.splice(i, 1);
        photoSelection.delete(i);
        render();
      } else if (act.startsWith('size-')) {
        arr[i].size = act.slice(5);
        render();
      }
    });

    return w;
  }

  function renderBatchBar() {
    const tb = document.querySelector('.photos-batch');
    if (!tb) return;
    const n = photoSelection.size;
    tb.hidden = n === 0;
    const c = tb.querySelector('.photos-batch__count');
    if (c) c.textContent = `${n} seleccionada${n === 1 ? '' : 's'}`;
  }

  function openPhotoLightbox(currentPhoto, list) {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal photo-lightbox';
    let idx = Math.max(0, list.findIndex(p => p.src === currentPhoto.src));
    function showCurrent() {
      const ph = list[idx]; if (!ph) return;
      dlg.querySelector('.photo-lightbox__img').src = absUrl(ph.src);
      dlg.querySelector('.photo-lightbox__caption').textContent = ph.caption || ph.src.split('/').pop();
      dlg.querySelector('.photo-lightbox__counter').textContent = `${idx + 1} / ${list.length}`;
    }
    dlg.innerHTML = `<div class="photo-lightbox__wrap">
      <button class="photo-lightbox__close" data-act="close" title="Cerrar (Esc)">×</button>
      <button class="photo-lightbox__nav photo-lightbox__nav--prev" data-act="prev" title="Anterior (←)">‹</button>
      <img class="photo-lightbox__img" alt="">
      <button class="photo-lightbox__nav photo-lightbox__nav--next" data-act="next" title="Siguiente (→)">›</button>
      <div class="photo-lightbox__footer">
        <span class="photo-lightbox__caption"></span>
        <span class="photo-lightbox__counter"></span>
      </div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    showCurrent();
    dlg.addEventListener('click', e => {
      const a = e.target.closest('[data-act]')?.dataset?.act;
      if (a === 'close' || e.target === dlg) { dlg.close(); dlg.remove(); }
      else if (a === 'prev') { idx = (idx - 1 + list.length) % list.length; showCurrent(); }
      else if (a === 'next') { idx = (idx + 1) % list.length; showCurrent(); }
    });
    dlg.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft') { idx = (idx - 1 + list.length) % list.length; showCurrent(); }
      else if (e.key === 'ArrowRight') { idx = (idx + 1) % list.length; showCurrent(); }
    });
  }

  // Cola de upload con concurrencia limitada, preview instantáneo + progreso visible.
  async function uploadFiles(fileList, addToGallery = false) {
    const files = Array.from(fileList).filter(f => f && f.type && f.type.startsWith('image/'));
    if (!files.length) return [];

    // PREVIEW INSTANTÁNEO: si vamos a galería, añadimos placeholders con objectURL ANTES de subir
    let placeholderStartIdx = -1;
    const objectUrls = [];
    if (addToGallery) {
      state.photos ||= [];
      placeholderStartIdx = state.photos.length;
      files.forEach(f => {
        const url = URL.createObjectURL(f);
        objectUrls.push(url);
        state.photos.push({
          src: url,                                    // preview temporal (objectURL)
          _uploading: true,
          _file: f.name,
          alt: f.name,
          caption: f.name.replace(/\.[^.]+$/, ''),
          size: 'normal',
        });
      });
      render(); // muestra los placeholders YA
    }

    const progress = makeUploadProgress(files.length);
    let done = 0, success = 0;
    const results = [];

    const queue = files.map((f, i) => ({ file: f, idx: i }));
    async function worker() {
      while (queue.length) {
        const item = queue.shift();
        const { file, idx } = item;
        progress.update(idx, 'subiendo', file.name);
        if (addToGallery && state.photos[placeholderStartIdx + idx]) {
          state.photos[placeholderStartIdx + idx]._progress = 'uploading';
          updateTilePreview(placeholderStartIdx + idx);
        }
        const fd = new FormData(); fd.append('file', file);
        try {
          const r = await api('upload_image', { method: 'POST', body: fd });
          results.push(r);
          if (addToGallery) {
            const ph = state.photos[placeholderStartIdx + idx];
            if (ph) {
              ph.src = r.src;
              delete ph._uploading;
              delete ph._progress;
              delete ph._file;
            }
            updateTilePreview(placeholderStartIdx + idx);
          }
          progress.update(idx, 'ok', file.name, r.bytes);
          success++;
        } catch (e) {
          progress.update(idx, 'err', file.name, 0, e.message);
          if (addToGallery && state.photos[placeholderStartIdx + idx]) {
            state.photos[placeholderStartIdx + idx]._progress = 'error';
            updateTilePreview(placeholderStartIdx + idx);
          }
        }
        done++;
        progress.setTotal(done, files.length);
      }
    }
    await Promise.all([worker(), worker(), worker()]);
    progress.finish(success, files.length);

    if (addToGallery) {
      // Quitar placeholders que fallaron (los _uploading que quedaron)
      state.photos = state.photos.filter(p => !p._uploading);
      objectUrls.forEach(u => URL.revokeObjectURL(u));
      if (success > 0) await saveSection('photos');
      render();
    }
    return results;
  }

  // Refresca solo el <img> de un tile específico sin re-renderizar todo
  function updateTilePreview(idx) {
    const tile = document.querySelector(`.photo-tile[data-idx="${idx}"]`);
    if (!tile) return;
    const ph = state.photos[idx]; if (!ph) return;
    const img = tile.querySelector('img');
    if (img) img.src = absUrl(ph.src);
    tile.classList.toggle('is-uploading', ph._progress === 'uploading');
    tile.classList.toggle('is-upload-error', ph._progress === 'error');
  }

  function makeUploadProgress(total) {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal upload-modal';
    dlg.innerHTML = `<div class="admin-modal__form" style="min-width:420px">
      <h2 style="margin-bottom:4px">Subiendo ${total} archivos</h2>
      <p class="field-help" id="upTotal" style="margin-bottom:14px">0 / ${total} completados</p>
      <div id="upList" style="max-height:50vh;overflow:auto;display:flex;flex-direction:column;gap:6px"></div>
      <div class="admin-modal__actions"><button class="btn btn--ghost" id="upClose" value="cancel">Cerrar al terminar</button></div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    const list = dlg.querySelector('#upList');
    const items = new Array(total).fill(null).map((_, i) => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12px;background:var(--surface-2);padding:6px 10px;border-radius:6px';
      row.innerHTML = `<span class="up-dot" style="width:8px;height:8px;border-radius:50%;background:var(--gray-400);flex-shrink:0"></span><span class="up-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span><span class="up-stat" style="font-family:var(--mono);color:var(--gray-400);font-size:11px"></span>`;
      list.appendChild(row);
      return row;
    });
    return {
      update(idx, status, name, bytes = 0, err = '') {
        const row = items[idx]; if (!row) return;
        row.querySelector('.up-name').textContent = name;
        const dot = row.querySelector('.up-dot');
        const stat = row.querySelector('.up-stat');
        if (status === 'ok')      { dot.style.background = 'var(--ok)';     stat.textContent = bytes > 0 ? `${(bytes/1024|0)} KB` : '✓'; }
        else if (status === 'err'){ dot.style.background = 'var(--danger)'; stat.textContent = err || 'error'; row.title = err; }
        else                      { dot.style.background = 'var(--coral)';  stat.textContent = '…'; }
      },
      setTotal(done, total) { dlg.querySelector('#upTotal').textContent = `${done} / ${total} completados`; },
      finish(success, total) {
        const btn = dlg.querySelector('#upClose');
        btn.textContent = `Cerrar (${success}/${total} ok)`;
        btn.addEventListener('click', () => { dlg.close(); dlg.remove(); });
        // auto-cierra si todo OK
        if (success === total) setTimeout(() => { dlg.close(); dlg.remove(); }, 1200);
      }
    };
  }

  // ─── INSTAGRAM ────────────────────────────────────────────────────
  function instagram() {
    const ig = state.instagram || (state.instagram = { title: 'Lo último en Instagram', subtitle: '', handle: 'celiaesceliaca', posts: [], widgetHtml: '' });
    const root = document.createElement('div');
    root.appendChild(secHeader('Instagram', 'Mostrar el feed real de Instagram. Lo más recomendable: usar un widget gratuito de LightWidget (5 min de setup).', [saveBtn('instagram')]));

    // Cabecera (siempre visible)
    const c1 = document.createElement('div'); c1.className = 'card';
    c1.innerHTML = `<h3 class="card__title">Cabecera</h3>
      <label class="field"><span>Título de sección</span><input class="input" data-k="title" value="${esc(ig.title || '')}"></label>
      <label class="field"><span>Subtítulo</span><input class="input" data-k="subtitle" value="${esc(ig.subtitle || '')}"></label>
      <label class="field"><span>Handle (sin @)</span><input class="input" data-k="handle" value="${esc(ig.handle || '')}"></label>`;
    c1.addEventListener('input', e => { const k = e.target.dataset.k; if (k) ig[k] = e.target.value; });
    root.appendChild(c1);

    // OPCION 1 - Widget externo (recomendado)
    const c2 = document.createElement('div'); c2.className = 'card';
    c2.innerHTML = `<h3 class="card__title">Opción 1 — Widget automático (recomendado)</h3>
      <p class="field-help" style="margin:0 0 14px">
        Pega el <code>&lt;iframe&gt;</code> que te dé un servicio de terceros — actualiza solo cada pocas horas.<br>
        Servicios permitidos (gratis con cuenta): <strong>LightWidget</strong>, <strong>SnapWidget</strong>, <strong>Elfsight</strong>, <strong>EmbedSocial</strong>, <strong>Behold</strong>, <strong>Curator</strong>.<br>
        <a href="https://lightwidget.com/" target="_blank" rel="noopener" style="color:var(--coral)">Crear widget en LightWidget →</a> (URL del perfil: <code>https://www.instagram.com/${esc(ig.handle || 'celiaesceliaca')}/</code>)
      </p>
      <label class="field"><span>HTML del widget (iframe)</span><textarea class="textarea" data-k="widgetHtml" rows="5" placeholder='<iframe src="https://cdn.lightwidget.com/widgets/..."></iframe>'>${esc(ig.widgetHtml || '')}</textarea></label>
      <p class="field-help">Si lo dejas vacío, se usa la Opción 2 (posts manuales). Por seguridad solo se aceptan iframes de los servicios listados.</p>`;
    c2.addEventListener('input', e => { const k = e.target.dataset.k; if (k) ig[k] = e.target.value; });
    root.appendChild(c2);

    // OPCION 2 - Posts manuales
    const c3 = document.createElement('div'); c3.className = 'card';
    c3.innerHTML = `<h3 class="card__title">Opción 2 — Posts manuales</h3>
      <p class="field-help" style="margin-bottom:14px">Solo se muestra si la Opción 1 está vacía. Formato: <code>https://www.instagram.com/p/CODIGO/</code> · <code>/reel/CODIGO/</code></p>`;
    const list = document.createElement('div'); list.className = 'list';
    (ig.posts || []).forEach((u, i) => list.appendChild(makeDraggable(igPostItem(u, i, ig), i)));
    makeSortable(list, (from, to) => moveItem(ig.posts, from, to));
    c3.appendChild(list);
    const add = document.createElement('button'); add.className = 'list-add'; add.textContent = '+ Añadir post';
    add.addEventListener('click', () => { (ig.posts ||= []).push(''); render(); });
    c3.appendChild(add);
    root.appendChild(c3);

    return root;
  }

  function igPostItem(url, i, parent) {
    const arr = parent.posts;
    const w = document.createElement('div'); w.className = 'list-item';
    const isValid = /^https:\/\/(www\.)?instagram\.com\/(p|reel)\/[\w-]+/i.test(url || '');
    w.innerHTML = `
      <label class="field">
        <span>URL del post #${i + 1}</span>
        <input class="input" value="${esc(url || '')}" placeholder="https://www.instagram.com/p/XXXX/">
      </label>
      ${url && !isValid ? '<div class="alert alert--err" style="margin:0;font-size:11px;padding:6px 10px">URL no parece de Instagram</div>' : ''}
    `;
    const inp = w.querySelector('input');
    inp.addEventListener('input', e => { arr[i] = e.target.value; });
    const a = document.createElement('div'); a.className = 'list-item__actions';
    if (i > 0) a.appendChild(mkBtn('↑', '', () => { swap(arr, i, i - 1); render(); }));
    if (i < arr.length - 1) a.appendChild(mkBtn('↓', '', () => { swap(arr, i, i + 1); render(); }));
    a.appendChild(mkBtn('Eliminar', 'danger', () => { arr.splice(i, 1); render(); }));
    w.appendChild(a);
    return w;
  }

  // ─── CONTACTO ───────────────────────────────────────────────────
  function contact() {
    const c = state.contact || (state.contact = { social: {} });
    if (!c.social) c.social = {};
    const root = document.createElement('div');
    root.appendChild(secHeader('Contacto', 'Emails públicos y redes sociales.', [saveBtn('contact')]));

    const card = document.createElement('div'); card.className = 'card';
    card.innerHTML = `<h3 class="card__title">Datos públicos de contacto</h3>
      <label class="field"><span>Email general (contacto)</span><input class="input" data-k="general" value="${esc(c.general || '')}"></label>
      <div class="list-item__grid">
        <label class="field"><span>Prensa — nombre</span><input class="input" data-k="pressName" value="${esc(c.pressName || '')}"></label>
        <label class="field"><span>Prensa — email</span><input class="input" data-k="pressEmail" value="${esc(c.pressEmail || '')}"></label>
      </div>
      <label class="field"><span>URL del sello</span><input class="input" data-k="labelUrl" value="${esc(c.labelUrl || '')}"></label>`;
    card.addEventListener('input', e => { const k = e.target.dataset.k; if (k) c[k] = e.target.value; });
    root.appendChild(card);

    const social = document.createElement('div'); social.className = 'card';
    social.innerHTML = `<h3 class="card__title">Redes sociales</h3>
      <div class="card__grid">
        ${['spotify','instagram','youtube','twitter','bandcamp','tiktok','facebook'].map(k => `<label class="field"><span>${k}</span><input class="input" data-sk="${k}" value="${esc(c.social[k] || '')}"></label>`).join('')}
      </div>`;
    social.addEventListener('input', e => { const k = e.target.dataset.sk; if (k) c.social[k] = e.target.value; });
    root.appendChild(social);
    return root;
  }

  // ─── FORMULARIO ──────────────────────────────────────────────────
  function form() {
    const f = state.form || (state.form = {});
    const root = document.createElement('div');
    root.appendChild(secHeader('Formulario de contacto', 'A dónde llegan los mensajes enviados desde la web.', [saveBtn('form')]));
    const card = document.createElement('div'); card.className = 'card';
    card.innerHTML = `<h3 class="card__title">Destinatarios</h3>
      <label class="field"><span>Destinatario principal (To)</span><input class="input" data-k="recipientTo" value="${esc(f.recipientTo || '')}"></label>
      <label class="field"><span>Copia oculta (Bcc)</span><input class="input" data-k="recipientBcc" value="${esc(f.recipientBcc || '')}"></label>
      <p class="field-help">Cuando alguien envía el formulario, llega a <strong>To</strong> con copia oculta a <strong>Bcc</strong>.</p>`;
    card.addEventListener('input', e => { const k = e.target.dataset.k; if (k) f[k] = e.target.value; });
    root.appendChild(card);
    return root;
  }

  // ─── Image picker ─────────────────────────────────────────────────
  function renderImgPicker(src, onChange) {
    const id = 'ip-' + Math.random().toString(36).slice(2, 8);
    setTimeout(() => {
      const el = document.getElementById(id); if (!el) return;
      const upBtn = el.querySelector('[data-act=upload]');
      const inp = el.querySelector('input[type=file]');
      const browseBtn = el.querySelector('[data-act=browse]');
      const srcInput = el.querySelector('input[type=text]');
      srcInput.addEventListener('input', e => onChange(e.target.value));
      upBtn.addEventListener('click', () => inp.click());
      inp.addEventListener('change', async () => {
        if (!inp.files[0]) return;
        const fd = new FormData(); fd.append('file', inp.files[0]);
        try {
          const r = await api('upload_image', { method: 'POST', body: fd });
          onChange(r.src);
          showToast('✓ ' + r.name);
        } catch (e) { showToast('Error: ' + e.message, 'err'); }
      });
      browseBtn.addEventListener('click', async () => {
        const r = await api('list_images');
        openImagePicker(r.images, onChange);
      });
    }, 0);
    return `<div class="img-picker" id="${id}">
      <div class="img-picker__preview" style="background-image:${cssUrl(src || '')}"></div>
      <div class="img-picker__meta">
        <input type="text" value="${esc(src || '')}" placeholder="ruta img/...">
      </div>
      <div class="img-picker__actions">
        <button class="btn btn--ghost btn--sm" data-act="browse">Biblioteca</button>
        <button class="btn btn--outline btn--sm" data-act="upload">Subir</button>
        <input type="file" accept="image/*" hidden>
      </div>
    </div>`;
  }

  function openImagePicker(images, onPick) {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal';
    dlg.style.maxWidth = '900px';
    dlg.innerHTML = `<div class="admin-modal__form">
      <h2>Biblioteca de imágenes</h2>
      <div class="photos-admin" style="max-height:60vh;overflow:auto">
        ${images.map(i => `<div class="photo-admin" data-src="${esc(i.src)}"><img src="${esc(absUrl(i.src))}" alt="" loading="lazy"><div class="photo-admin__size-tag">${(i.size/1024|0)}K</div></div>`).join('')}
      </div>
      <div class="admin-modal__actions"><button class="btn btn--ghost" value="cancel">Cancelar</button></div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    dlg.addEventListener('click', e => {
      const tile = e.target.closest('.photo-admin');
      if (tile) { onPick(tile.dataset.src); dlg.close(); dlg.remove(); }
      else if (e.target.tagName === 'BUTTON') { dlg.close(); dlg.remove(); }
    });
  }

  // ─── Utilidades genéricas ────────────────────────────────────────
  function mkBtn(label, kind, fn) {
    const b = document.createElement('button');
    b.className = 'btn ' + (kind ? 'btn--' + kind : 'btn--ghost') + ' btn--sm';
    b.textContent = label;
    b.addEventListener('click', fn);
    return b;
  }
  function swap(a, i, j) { const t = a[i]; a[i] = a[j]; a[j] = t; }
  function moveItem(a, from, to) {
    if (from === to || from < 0 || to < 0 || from >= a.length || to >= a.length) return;
    const [el] = a.splice(from, 1);
    a.splice(to, 0, el);
  }

  /**
   * Hace que los hijos de container sean reordenables por drag&drop.
   * Llama a onReorder(from, to) cuando hay un movimiento valido.
   * Cada hijo debe tener un atributo data-idx="N".
   */
  function makeSortable(container, onReorder) {
    if (!container || container.dataset.sortableInit === '1') return;
    container.dataset.sortableInit = '1';
    let dragging = null;
    let placeholder = null;

    container.addEventListener('dragstart', e => {
      const tile = e.target.closest('[data-idx]');
      if (!tile || tile.parentElement !== container) return;
      dragging = tile;
      tile.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', tile.dataset.idx);
    });
    container.addEventListener('dragend', () => {
      if (dragging) dragging.classList.remove('is-dragging');
      if (placeholder) placeholder.remove();
      placeholder = null;
      container.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
      dragging = null;
    });
    container.addEventListener('dragover', e => {
      if (!dragging) return;
      e.preventDefault();
      const tile = e.target.closest('[data-idx]');
      if (!tile || tile === dragging || tile.parentElement !== container) return;
      container.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target'));
      tile.classList.add('drop-target');
    });
    container.addEventListener('drop', e => {
      if (!dragging) return;
      e.preventDefault();
      const tile = e.target.closest('[data-idx]');
      if (!tile || tile === dragging || tile.parentElement !== container) return;
      const from = parseInt(dragging.dataset.idx, 10);
      const to = parseInt(tile.dataset.idx, 10);
      onReorder(from, to);
      render();
    });
  }

  // Helper que añade el handle visual + atributos draggable a un item
  function makeDraggable(el, idx) {
    el.setAttribute('draggable', 'true');
    el.setAttribute('data-idx', String(idx));
    if (!el.querySelector('.list-item__drag-handle')) {
      const handle = document.createElement('span');
      handle.className = 'list-item__drag-handle';
      handle.setAttribute('title', 'Arrastra para reordenar');
      handle.textContent = '⋮⋮';
      el.appendChild(handle);
    }
    return el;
  }

  // ─── Preview en vivo ─────────────────────────────────────────────
  const previewPanel = $('#adminPreview');
  const previewFrame = $('#previewFrame');
  const previewScope = $('#previewScope');
  let previewVisible = localStorage.getItem('previewVisible') === '1';

  function togglePreview(force) {
    previewVisible = force ?? !previewVisible;
    document.body.classList.toggle('has-preview', previewVisible);
    previewPanel.hidden = !previewVisible;
    localStorage.setItem('previewVisible', previewVisible ? '1' : '0');
    if (previewVisible) reloadPreview();
  }

  function reloadPreview() {
    if (!previewVisible) return;
    const scope = previewScope.value;
    const sectionMap = { hero: '', bio: 'bio', members: 'bio', discography: 'musica', videos: 'videos', concerts: 'conciertos', press: 'prensa', instagram: 'instagram', photos: 'fotos', contact: 'contacto', form: 'contacto' };
    const target = scope === 'auto' ? sectionMap[currentSection] : (scope === 'hero' ? '' : scope);
    const hash = target ? '#' + target : '';
    previewFrame.src = '/' + hash + (hash.includes('?') ? '&' : '?') + '_t=' + Date.now();
  }

  $('#previewBtn').addEventListener('click', () => togglePreview());
  $('#previewClose').addEventListener('click', () => togglePreview(false));
  $('#previewReload').addEventListener('click', reloadPreview);
  previewScope.addEventListener('change', reloadPreview);

  // Activar al inicio si estaba activo
  setTimeout(() => { if (previewVisible) togglePreview(true); }, 100);

  // ─── Save All + Dirty indicator ──────────────────────────────────
  $('#saveAllBtn').addEventListener('click', async () => {
    $('#saveAllBtn').disabled = true;
    await saveAll();
    $('#saveAllBtn').disabled = false;
    updateDirtyDot();
  });
  function updateDirtyDot() { $('#dirtyDot').hidden = !isDirty(); }
  // Poll suave del estado dirty (cada vez que cambias input, sale el dot)
  document.addEventListener('input', updateDirtyDot);
  document.addEventListener('click', () => setTimeout(updateDirtyDot, 50));

  // ─── Confirmaciones para acciones destructivas ───────────────────
  // Aplica solo a botones .btn--danger dentro de .list-item (NO a photo-tiles que ya tienen su propio confirm).
  document.addEventListener('click', e => {
    const btn = e.target.closest('.list-item .btn--danger');
    if (!btn || btn.dataset.confirmed === '1') return;
    if (btn.id === 'pwCancel' || btn.id === 'dCancel') return;
    const txt = btn.textContent.trim();
    if (txt === '×' || txt === 'Eliminar') {
      e.preventDefault(); e.stopImmediatePropagation();
      if (confirm('¿Eliminar este elemento? No se puede deshacer (pero hay backup automático del JSON).')) {
        btn.dataset.confirmed = '1';
        btn.click();
        delete btn.dataset.confirmed;
      }
    }
  }, true);

  // ─── Historial de cambios ────────────────────────────────────────
  $('#historyBtn').addEventListener('click', async () => {
    try {
      const r = await api('list_backups');
      openHistoryDialog(r.backups);
    } catch (e) { showToast('Error: ' + e.message, 'err'); }
  });

  function openHistoryDialog(backups) {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal';
    dlg.style.maxWidth = '560px';
    const list = backups.map(b => {
      const dt = new Date(b.mtime * 1000);
      const human = dt.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
      const rel = relativeTime(dt);
      return `<div class="history-row">
        <div>
          <strong>${human}</strong>
          <div class="field-help" style="margin:0">${rel} · ${(b.size/1024|0)} KB</div>
        </div>
        <button class="btn btn--outline btn--sm" data-restore="${esc(b.name)}">Restaurar</button>
      </div>`;
    }).join('');
    dlg.innerHTML = `<div class="admin-modal__form">
      <h2>Historial de cambios</h2>
      <p class="field-help" style="margin:0 0 14px">Guardamos los últimos 20 estados. Restaurar reemplaza todo el contenido (también queda en backup).</p>
      <div class="history-list">${list || '<p class="field-help">Aún no hay backups.</p>'}</div>
      <div class="admin-modal__actions"><button class="btn btn--ghost" value="cancel">Cerrar</button></div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    dlg.addEventListener('click', async e => {
      const restore = e.target?.dataset?.restore;
      if (restore) {
        if (!confirm('¿Restaurar este estado? Reemplazará el contenido actual (se hará un backup del actual antes).')) return;
        e.target.disabled = true; e.target.textContent = 'Restaurando…';
        try {
          const r = await api('restore_backup', { method: 'POST', json: { name: restore } });
          state = r.data; savedSnapshot = JSON.stringify(state);
          showToast('✓ Restaurado');
          dlg.close(); dlg.remove();
          render();
        } catch (err) { showToast('Error: ' + err.message, 'err'); e.target.disabled = false; e.target.textContent = 'Restaurar'; }
      } else if (e.target.tagName === 'BUTTON' && e.target.value === 'cancel') {
        dlg.close(); dlg.remove();
      }
    });
  }

  function relativeTime(date) {
    const sec = (Date.now() - date.getTime()) / 1000;
    if (sec < 60) return 'hace ' + Math.round(sec) + 's';
    if (sec < 3600) return 'hace ' + Math.round(sec / 60) + ' min';
    if (sec < 86400) return 'hace ' + Math.round(sec / 3600) + ' h';
    return 'hace ' + Math.round(sec / 86400) + ' d';
  }

  // ─── Cambio de contraseña ────────────────────────────────────────
  $('#changePwBtn').addEventListener('click', () => $('#pwDialog').showModal());

  // ─── 2FA setup/disable ────────────────────────────────────────
  $('#twoFaBtn').addEventListener('click', async () => {
    try {
      const s = await api('session');
      if (s.twoFactorEnabled) open2faDisable();
      else open2faSetup();
    } catch (e) { showToast('Error: ' + e.message, 'err'); }
  });

  async function open2faSetup() {
    try {
      const r = await api('2fa_setup');
      const dlg = document.createElement('dialog');
      dlg.className = 'admin-modal';
      dlg.style.maxWidth = '480px';
      const qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=4&data=' + encodeURIComponent(r.uri);
      dlg.innerHTML = `<div class="admin-modal__form">
        <h2>Activar 2FA</h2>
        <p class="field-help">Escanea el QR con Google Authenticator, Authy o 1Password. Si no puedes escanear, copia el secret manualmente.</p>
        <div style="text-align:center;margin:14px 0"><img src="${qrSrc}" alt="QR code" width="220" height="220" style="border-radius:8px;background:#fff;padding:6px"></div>
        <label class="field"><span>Secret (manual)</span><input class="input" readonly value="${esc(r.secret)}" style="font-family:var(--mono);font-size:13px;letter-spacing:.05em" onclick="this.select()"></label>
        <label class="field"><span>Código de 6 dígitos para confirmar</span><input class="input" id="totpConfirm" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456"></label>
        <div class="admin-modal__actions">
          <button class="btn btn--ghost" id="totpCancel">Cancelar</button>
          <button class="btn btn--primary" id="totpEnable">Activar</button>
        </div>
      </div>`;
      document.body.appendChild(dlg);
      dlg.showModal();
      dlg.querySelector('#totpCancel').addEventListener('click', () => { dlg.close(); dlg.remove(); });
      dlg.querySelector('#totpEnable').addEventListener('click', async () => {
        const code = dlg.querySelector('#totpConfirm').value;
        try {
          const r2 = await api('2fa_confirm', { method: 'POST', json: { code } });
          dlg.close(); dlg.remove();
          showRecoveryCodes(r2.recoveryCodes);
        } catch (e) { showToast('Error: ' + e.message, 'err'); }
      });
    } catch (e) { showToast('Error: ' + e.message, 'err'); }
  }

  function showRecoveryCodes(codes) {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal';
    dlg.innerHTML = `<div class="admin-modal__form">
      <h2>✓ 2FA activado</h2>
      <p class="field-help">Guarda estos códigos de recuperación en un lugar seguro. Sirven para entrar si pierdes el móvil. <strong>Cada código solo se puede usar una vez.</strong></p>
      <pre style="background:var(--surface-2);padding:14px;border-radius:8px;font-family:var(--mono);font-size:14px;line-height:1.8;text-align:center">${codes.map(esc).join('\n')}</pre>
      <div class="admin-modal__actions">
        <button class="btn btn--outline" id="copyCodes">Copiar al portapapeles</button>
        <button class="btn btn--primary" id="closeCodes">Ya los guardé</button>
      </div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    dlg.querySelector('#copyCodes').addEventListener('click', () => {
      navigator.clipboard.writeText(codes.join('\n')).then(() => showToast('✓ Copiado'));
    });
    dlg.querySelector('#closeCodes').addEventListener('click', () => { dlg.close(); dlg.remove(); });
  }

  function open2faDisable() {
    const dlg = document.createElement('dialog');
    dlg.className = 'admin-modal';
    dlg.innerHTML = `<div class="admin-modal__form">
      <h2>Desactivar 2FA</h2>
      <p class="field-help">Para desactivar, introduce el código actual de tu app autenticadora.</p>
      <label class="field"><span>Código actual</span><input class="input" id="disableCode" type="text" inputmode="numeric" maxlength="6" placeholder="123456"></label>
      <div class="admin-modal__actions">
        <button class="btn btn--ghost" id="dCancel">Cancelar</button>
        <button class="btn btn--danger" id="dConfirm">Desactivar</button>
      </div>
    </div>`;
    document.body.appendChild(dlg);
    dlg.showModal();
    dlg.querySelector('#dCancel').addEventListener('click', () => { dlg.close(); dlg.remove(); });
    dlg.querySelector('#dConfirm').addEventListener('click', async () => {
      try {
        await api('2fa_disable', { method: 'POST', json: { code: dlg.querySelector('#disableCode').value } });
        dlg.close(); dlg.remove();
        showToast('2FA desactivado');
      } catch (e) { showToast('Error: ' + e.message, 'err'); }
    });
  }
  $('#pwCancel').addEventListener('click', () => $('#pwDialog').close());
  $('#pwForm').addEventListener('submit', async e => {
    e.preventDefault();
    try {
      await api('change_password', { method: 'POST', json: { current: $('#pwCurrent').value, new: $('#pwNew').value } });
      $('#pwCurrent').value = ''; $('#pwNew').value = '';
      $('#pwDialog').close();
      showToast('✓ Contraseña actualizada');
    } catch (e) { showToast('Error: ' + e.message, 'err'); }
  });

  // ─── Init ───────────────────────────────────────────────────────
  loadAll().catch(e => showToast('Error: ' + e.message, 'err'));
})();
