import { message as antMessage } from 'ant-design-vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

antMessage.config({ top: '72px', duration: 3, maxCount: 3 });

/* ------------------------------------------------------------------
 * Alertas estilo SweetAlert (SweetAlert2) con los colores de CEEC.
 * ------------------------------------------------------------------ */
const COLOR = {
    primario: '#1b75bc',
    peligro: '#dc2626',
    cancelar: '#6e7881',
};

const swal = Swal.mixin({
    buttonsStyling: true,
    heightAuto: false,             // no alterar la altura de <body> (descuadra los layouts)
    reverseButtons: true,          // "Cancelar" a la izquierda, acción a la derecha
    focusCancel: false,
    confirmButtonColor: COLOR.primario,
    cancelButtonColor: COLOR.cancelar,
    cancelButtonText: 'Cancelar',
    customClass: { popup: 'ceec-swal' },
});

const escapar = (t) => String(t ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * Aviso de advertencia (modal con el ícono "!" de SweetAlert).
 */
export function alertaAdvertencia(texto, titulo = 'Atención') {
    return swal.fire({
        icon: 'warning',
        title: titulo,
        text: texto,
        confirmButtonText: 'Entendido',
    });
}

/**
 * Aviso pequeño en la esquina (toast de SweetAlert); no interrumpe lo que hace el usuario.
 * Útil, por ejemplo, para los avisos de tiempo durante un examen.
 */
export function avisoToast(texto, { icon = 'warning', segundos = 5 } = {}) {
    return Swal.fire({
        toast: true,
        heightAuto: false,
        position: 'top-end',
        icon,
        title: texto,
        showConfirmButton: false,
        timer: segundos * 1000,
        timerProgressBar: true,
        customClass: { container: 'ceec-swal-toasts', popup: `ceec-swal ceec-swal--toast ceec-swal--${icon}` },
        didOpen: (el) => {
            el.addEventListener('mouseenter', Swal.stopTimer);
            el.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
}

// ¿Hay una alerta/confirmación de SweetAlert abierta? (un toast la reemplazaría)
const hayAlertaAbierta = () => Swal.isVisible() && !Swal.getPopup()?.classList.contains('swal2-toast');

const toastOMensaje = (icon, tipoAnt) => (contenido, duracion) => {
    const texto = typeof contenido === 'object' && contenido !== null ? contenido.content : contenido;
    if (hayAlertaAbierta()) return antMessage[tipoAnt](texto, duracion);
    return avisoToast(texto, { icon, segundos: typeof duracion === 'number' && duracion > 0 ? duracion : 3.5 });
};

/**
 * Éxito, error e información: toast de SweetAlert en la esquina superior derecha.
 * Advertencias: alerta de SweetAlert con botón "Entendido".
 * (message.loading y lo demás siguen siendo los de Ant Design).
 */
const reemplazos = {
    success: toastOMensaje('success', 'success'),
    error: toastOMensaje('error', 'error'),
    info: toastOMensaje('info', 'info'),
    warning: (contenido) => alertaAdvertencia(typeof contenido === 'object' && contenido !== null ? contenido.content : contenido),
};
reemplazos.warn = reemplazos.warning;

export const message = new Proxy(antMessage, {
    get(target, prop) {
        if (prop in reemplazos) return reemplazos[prop];
        const v = target[prop];
        return typeof v === 'function' ? v.bind(target) : v;
    },
});

/**
 * Muestra los mensajes flash de Inertia (page.props.flash).
 */
export function showFlash(flash) {
    if (!flash) return;
    if (flash.success) message.success(flash.success);
    if (flash.error) message.error(flash.error);
    if (flash.warning) message.warning(flash.warning);
    if (flash.info) message.info(flash.info);
}

/**
 * Diálogo de confirmación estilo SweetAlert.
 *
 * @param {object}  opts
 * @param {string}  opts.title
 * @param {string}  opts.content     texto descriptivo
 * @param {string}  opts.okText
 * @param {string}  opts.cancelText
 * @param {boolean} opts.danger      botón principal en rojo
 * @param {'delete'|'warn'|'ask'|'logout'} opts.tone   ícono del diálogo
 * @param {Function} opts.onOk       puede devolver una promesa (el botón muestra "cargando")
 * @param {Function} opts.onCancel
 */
function baseConfirm({ title, content = '', nota = '', okText = 'Confirmar', cancelText = 'Cancelar', danger = false, tone = 'ask', onOk, onCancel }) {
    const iconos = { delete: 'warning', warn: 'warning', ask: 'question', logout: 'question' };

    const html = [
        content ? `<div class="ceec-swal__content">${escapar(content)}</div>` : '',
        nota ? `<div class="ceec-swal__nota">${escapar(nota)}</div>` : '',
    ].join('');

    return swal.fire({
        icon: iconos[tone] ?? 'question',
        title,
        html: html || undefined,
        showCancelButton: true,
        confirmButtonText: okText,
        cancelButtonText: cancelText,
        confirmButtonColor: danger ? COLOR.peligro : COLOR.primario,
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        allowEscapeKey: () => !Swal.isLoading(),
        preConfirm: async () => {
            try {
                await onOk?.();
            } catch (e) {
                // Quien llama ya muestra su propio mensaje de error.
            }
            return true;
        },
    }).then((r) => {
        if (r.isDismissed) onCancel?.();
        return r;
    });
}

/**
 * Diálogo de confirmación de borrado.
 */
export function confirmDelete({ title = '¿Eliminar registro?', content = '', onOk, onCancel }) {
    return baseConfirm({
        title, content, nota: 'Esta acción no se puede deshacer.',
        okText: 'Sí, eliminar', danger: true, tone: 'delete', onOk, onCancel,
    });
}

/**
 * Diálogo de confirmación genérico.
 */
export function confirmAction({ title, content = '', okText = 'Confirmar', cancelText = 'Cancelar', danger = false, tone, onOk, onCancel }) {
    return baseConfirm({ title, content, okText, cancelText, danger, tone: tone ?? (danger ? 'warn' : 'ask'), onOk, onCancel });
}
