/**
 * Utilidades de contraseña compartidas por el registro de cuenta y el alta de estudiantes.
 */

// Sin caracteres ambiguos (0/O, 1/l/I) para que se puedan dictar o copiar a mano.
const MAYUS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
const MINUS = 'abcdefghijkmnpqrstuvwxyz';
const DIGITOS = '23456789';
const SIMBOLOS = '#$%&*+?@';

function azar(max) {
    const buf = new Uint32Array(1);
    crypto.getRandomValues(buf);
    return buf[0] % max;
}

const tomar = (set) => set[azar(set.length)];

/** Genera una contraseña aleatoria con mayúsculas, minúsculas, dígitos y un símbolo. */
export function generarContrasena(largo = 10) {
    const todos = MAYUS + MINUS + DIGITOS + SIMBOLOS;
    const chars = [tomar(MAYUS), tomar(MINUS), tomar(DIGITOS), tomar(SIMBOLOS)];
    while (chars.length < largo) chars.push(tomar(todos));
    for (let i = chars.length - 1; i > 0; i--) {
        const j = azar(i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
}

/**
 * Estado del campo "Confirmar contraseña" para a-form-item.
 * Devuelve { status, help } listo para :validate-status y :help.
 */
export function estadoConfirmacion(password, confirmacion, errorServidor = null) {
    if (errorServidor) return { status: 'error', help: errorServidor };
    if (!confirmacion) return { status: undefined, help: undefined };
    if (confirmacion === password) return { status: 'success', help: 'Las contraseñas coinciden' };
    if (password && password.startsWith(confirmacion)) return { status: 'warning', help: 'Aún no coincide, sigue escribiendo…' };
    return { status: 'error', help: 'Las contraseñas no coinciden' };
}
