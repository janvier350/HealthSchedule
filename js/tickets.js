/**
 * js/tickets.js — Dictado por voz y adjuntos (pegar/soltar/seleccionar) para
 * el módulo de Solicitudes/Tickets.
 *
 * Voz: botones con clase .mic-btn y data-target="#idDelCampo".
 *      Usa la Web Speech API (Chrome/Edge). Si no está disponible, se oculta.
 * Adjuntos: contenedores con clase .ticket-attach que incluyen:
 *      input[type=file][data-role=input], un .attach-drop (zona para pegar/soltar)
 *      y un .attach-thumbs (miniaturas). Se exponen los archivos vía
 *      window.TicketAttach.get(contenedor) -> [File,...].
 */
(function () {
    'use strict';

    // ── Dictado por voz ───────────────────────────────────────────────
    function initMic() {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        var btns = document.querySelectorAll('.mic-btn');
        if (!SR) { btns.forEach(function (b) { b.style.display = 'none'; }); return; }
        var lang = (document.documentElement.lang || 'es').toLowerCase().indexOf('en') === 0 ? 'en-US' : 'es-ES';

        btns.forEach(function (btn) {
            var target = document.querySelector(btn.getAttribute('data-target'));
            if (!target) { btn.style.display = 'none'; return; }
            var rec = null, escuchando = false;
            btn.addEventListener('click', function () {
                if (escuchando && rec) { rec.stop(); return; }
                rec = new SR();
                rec.lang = lang;
                rec.interimResults = false;
                rec.continuous = true;
                var base = target.value;
                rec.onstart = function () { escuchando = true; btn.classList.add('btn-danger'); btn.classList.remove('btn-outline-secondary'); };
                rec.onend   = function () { escuchando = false; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); };
                rec.onerror = function () { escuchando = false; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); };
                rec.onresult = function (e) {
                    var txt = '';
                    for (var i = e.resultIndex; i < e.results.length; i++) {
                        if (e.results[i].isFinal) txt += e.results[i][0].transcript;
                    }
                    if (txt) {
                        base = (base ? base.replace(/\s*$/, '') + ' ' : '') + txt.trim();
                        target.value = base;
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                };
                rec.start();
            });
        });
    }

    // ── Adjuntos (pegar / soltar / seleccionar) ───────────────────────
    var store = new WeakMap(); // contenedor -> [File,...]

    function esImagenOPdf(f) {
        return f && (/^image\//.test(f.type) || f.type === 'application/pdf');
    }

    function render(cont) {
        var thumbs = cont.querySelector('.attach-thumbs');
        if (!thumbs) return;
        var files = store.get(cont) || [];
        thumbs.innerHTML = '';
        files.forEach(function (f, idx) {
            var chip = document.createElement('div');
            chip.className = 'attach-thumb border rounded p-1 d-inline-flex align-items-center me-2 mb-2';
            chip.style.gap = '6px';
            if (/^image\//.test(f.type)) {
                var img = document.createElement('img');
                img.style.cssText = 'width:42px;height:42px;object-fit:cover;border-radius:4px;';
                img.src = URL.createObjectURL(f);
                chip.appendChild(img);
            } else {
                var ic = document.createElement('i');
                ic.className = 'bi bi-file-earmark-pdf text-danger fs-4';
                chip.appendChild(ic);
            }
            var name = document.createElement('span');
            name.className = 'small text-truncate';
            name.style.maxWidth = '140px';
            name.textContent = f.name || 'captura.png';
            chip.appendChild(name);
            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'btn-close btn-close-sm';
            x.setAttribute('aria-label', 'Quitar');
            x.addEventListener('click', function () {
                var arr = store.get(cont) || [];
                arr.splice(idx, 1);
                store.set(cont, arr);
                render(cont);
            });
            chip.appendChild(x);
            thumbs.appendChild(chip);
        });
    }

    function add(cont, fileList) {
        var arr = store.get(cont) || [];
        Array.prototype.forEach.call(fileList, function (f) {
            if (esImagenOPdf(f) && f.size <= 10 * 1024 * 1024) arr.push(f);
        });
        store.set(cont, arr);
        render(cont);
    }

    function initAttach() {
        document.querySelectorAll('.ticket-attach').forEach(function (cont) {
            if (cont.__init) return; cont.__init = true;
            store.set(cont, []);
            var input = cont.querySelector('input[type=file][data-role=input]');
            var drop  = cont.querySelector('.attach-drop');

            if (input) {
                input.addEventListener('change', function () { add(cont, input.files); input.value = ''; });
            }
            if (drop) {
                drop.addEventListener('paste', function (e) {
                    if (e.clipboardData && e.clipboardData.files && e.clipboardData.files.length) {
                        add(cont, e.clipboardData.files); e.preventDefault();
                    }
                });
                ['dragover', 'dragenter'].forEach(function (ev) {
                    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('border-primary'); });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('border-primary'); });
                });
                drop.addEventListener('drop', function (e) {
                    if (e.dataTransfer && e.dataTransfer.files) add(cont, e.dataTransfer.files);
                });
            }
        });
    }

    window.TicketAttach = {
        get: function (cont) { return store.get(cont) || []; },
        clear: function (cont) { store.set(cont, []); render(cont); }
    };

    document.addEventListener('DOMContentLoaded', function () { initMic(); initAttach(); });
})();
