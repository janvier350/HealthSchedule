/**
 * modal_stack.js — Apila correctamente modales de Bootstrap anidados.
 * Sin esto, al abrir un segundo modal (p. ej. "Informe de Atención") sobre
 * otro (p. ej. el historial del paciente), el nuevo modal aparecía DETRÁS del
 * primero y no se veía hasta cerrar el de atrás.
 *
 * - Al abrir un modal cuando ya hay otro abierto, se le sube el z-index (y al
 *   de su fondo/backdrop) por encima del anterior.
 * - Al cerrar un modal, si aún queda otro abierto, se restaura el bloqueo de
 *   scroll del body (Bootstrap lo quita aunque queden modales abiertos).
 */
(function () {
    if (!document || !document.addEventListener) return;

    document.addEventListener('show.bs.modal', function (e) {
        var abiertos = document.querySelectorAll('.modal.show').length; // ya abiertos
        if (abiertos > 0 && e.target) {
            var z = 1055 + abiertos * 20;
            e.target.style.zIndex = z;
            // El backdrop del nuevo modal se crea justo después; súbelo también.
            window.setTimeout(function () {
                var bds = document.querySelectorAll('.modal-backdrop:not(.modal-stack-done)');
                var bd = bds[bds.length - 1];
                if (bd) { bd.style.zIndex = (z - 5); bd.classList.add('modal-stack-done'); }
            }, 0);
        }
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target) e.target.style.zIndex = '';
        // Si todavía hay un modal abierto, mantener el scroll bloqueado.
        if (document.querySelectorAll('.modal.show').length > 0) {
            document.body.classList.add('modal-open');
        }
    });
})();
