/**
 * CURP: normalización y validación local (misma lógica que App\Support\Curp),
 * más la consulta al backend que valida contra RENAPO y devuelve los datos.
 */
import axios from 'axios';

const PATRON = new RegExp(
    '^[A-Z][AEIOUX][A-Z]{2}\\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\\d|3[01])[HMX]'
    + '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)'
    + '[B-DF-HJ-NP-TV-Z]{3}[A-Z\\d]\\d$',
);
const DICCIONARIO = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

export const normalizarCurp = (v) => String(v ?? '').toUpperCase().replace(/[^A-Z0-9Ñ]/g, '').slice(0, 18);

function fechaValida(curp) {
    const anio = (/\d/.test(curp[16]) ? 1900 : 2000) + Number(curp.slice(4, 6));
    const mes = Number(curp.slice(6, 8));
    const dia = Number(curp.slice(8, 10));
    const f = new Date(anio, mes - 1, dia);
    return f.getFullYear() === anio && f.getMonth() === mes - 1 && f.getDate() === dia;
}

function digitoVerificador(curp) {
    let suma = 0;
    for (let i = 0; i < 17; i++) suma += DICCIONARIO.indexOf(curp[i]) * (18 - i);
    return (10 - (suma % 10)) % 10;
}

/** Mensaje de error de formato, o null si la CURP es válida. */
export function errorCurp(curp) {
    if (curp.length !== 18) return 'La CURP debe tener exactamente 18 caracteres.';
    if (!PATRON.test(curp) || !fechaValida(curp)) return 'La CURP no tiene un formato válido.';
    if (Number(curp[17]) !== digitoVerificador(curp)) return 'La CURP no es válida (el dígito verificador no coincide).';
    return null;
}

/**
 * Valida la CURP en el servidor (RENAPO si está configurado).
 * @returns {Promise<{valida: boolean, mensaje: ?string, fuente: 'renapo'|'local', datos: ?object}>}
 */
export async function consultarCurp(curp) {
    const { data } = await axios.get(route('curp.validar'), { params: { curp } });
    return data;
}
